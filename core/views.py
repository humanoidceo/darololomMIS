from django.shortcuts import render, redirect, get_object_or_404
from django.urls import reverse
from django.core.paginator import Paginator
from django.db.models import Q, Count, Prefetch
from django.contrib import messages
from django.http import FileResponse, Http404, JsonResponse
from django.conf import settings
from django.views.decorators.http import require_POST
import os
from .models import Student, SchoolClass, Subject, Teacher, StudyLevel, CoursePeriod, Semester, TeacherContract
from .models import StudentBehavior, TeacherBehavior
from .forms import StudentForm, SchoolClassForm, SubjectForm, TeacherForm, TeacherContractForm
from .models import StudentScore
import json
from django.utils.safestring import mark_safe
from django.utils import timezone


def _persian_to_ascii(s: str) -> str:
	"""Convert Persian digits to ASCII digits."""
	mapping = {
		'۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
		'۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
	}
	return ''.join(mapping.get(ch, ch) for ch in s)


def _gregorian_to_jalali(gy: int, gm: int, gd: int):
	"""Convert Gregorian date to Jalali (Shamsi). Returns (jy, jm, jd)."""
	g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334]
	if gy > 1600:
		jy = 979
		gy -= 1600
	else:
		jy = 0
		gy -= 621
	gy2 = gy + 1 if gm > 2 else gy
	days = (
		365 * gy
		+ (gy2 + 3) // 4
		- (gy2 + 99) // 100
		+ (gy2 + 399) // 400
		- 80
		+ gd
		+ g_d_m[gm - 1]
	)
	jy += 33 * (days // 12053)
	days %= 12053
	jy += 4 * (days // 1461)
	days %= 1461
	if days > 365:
		jy += (days - 1) // 365
		days = (days - 1) % 365
	if days < 186:
		jm = 1 + days // 31
		jd = 1 + days % 31
	else:
		jm = 7 + (days - 186) // 30
		jd = 1 + (days - 186) % 30
	return jy, jm, jd


def _ensure_reference_data():
	"""Ensure study levels, semesters, and periods exist."""
	level_defs = [
		('aali', 'عالی'),
		('moteseta', 'متوسطه'),
		('ebtedai', 'ابتداییه'),
	]
	level_map = {}
	for code, name in level_defs:
		obj, _ = StudyLevel.objects.get_or_create(code=code, defaults={'name': name})
		if obj.name != name:
			obj.name = name
			obj.save(update_fields=['name'])
		level_map[code] = obj

	for n in range(1, 5):
		Semester.objects.get_or_create(number=n)
	for n in range(1, 7):
		CoursePeriod.objects.get_or_create(number=n)

	return level_map


def student_create(request):
	level_map = _ensure_reference_data()
	if request.method == 'POST':
		form = StudentForm(request.POST, request.FILES)
		if form.is_valid():
			form.save()
			messages.success(request, 'دانش‌آموز با موفقیت ثبت شد.')
			return redirect(reverse('core:student_list'))
	else:
		form = StudentForm()
	level_ids = {k: v.id for k, v in level_map.items()}
	period_names = [{'value': str(p.id), 'label': str(p)} for p in CoursePeriod.objects.order_by('number')]
	return render(request, 'core/student_form_clean.html', {
		'form': form,
		'level_ids': level_ids,
		'period_names': period_names,
		'student_periods_ebtedai': [],
		'student_periods_moteseta': [],
	})


def student_list(request):
	"""نمایش لیست دانش‌آموزان با قابلیت جستجو و صفحه‌بندی."""
	level_map = _ensure_reference_data()
	level_param = request.GET.get('level', '').strip()
	if level_param not in level_map:
		level_param = 'aali'
	q = request.GET.get('q', '').strip()
	page_size_raw = request.GET.get('page_size', '20')
	allowed_page_sizes = {10, 20, 50, 100}
	try:
		page_size = int(page_size_raw)
	except (TypeError, ValueError):
		page_size = 20
	if page_size not in allowed_page_sizes:
		page_size = 20
	students = Student.objects.all().order_by('-created_at')
	if level_param in level_map:
		level_obj = level_map[level_param]
		students = students.filter(Q(level=level_obj) | Q(level__isnull=True, school_class__level=level_obj))
	if q:
		students = students.filter(
			Q(name__icontains=q) | Q(father_name__icontains=q) | Q(mobile_number__icontains=q)
		)
	students = students.annotate(
		merit_count=Count('behavior_entries', filter=Q(behavior_entries__entry_type='merit'), distinct=True),
	)
	students = students.prefetch_related(
		Prefetch('behavior_entries', queryset=StudentBehavior.objects.order_by('-created_at'))
	)

	paginator = Paginator(students, page_size)
	page_number = request.GET.get('page')
	page_obj = paginator.get_page(page_number)

	context = {
		'q': q,
		'page_obj': page_obj,
		'selected_level': level_param,
		'page_size': page_size,
	}
	return render(request, 'core/student_list.html', context)


def teacher_list(request):
	"""نمایش لیست اساتید مشابه لیست دانش‌آموزان با جستجو و صفحه‌بندی."""
	q = request.GET.get('q', '').strip()
	page_size_raw = request.GET.get('page_size', '20')
	allowed_page_sizes = {10, 20, 50, 100}
	try:
		page_size = int(page_size_raw)
	except (TypeError, ValueError):
		page_size = 20
	if page_size not in allowed_page_sizes:
		page_size = 20
	teachers = Teacher.objects.all().order_by('-created_at')
	if q:
		teachers = teachers.filter(
			Q(name__icontains=q) | Q(father_name__icontains=q) | Q(id_number__icontains=q)
		)
	teachers = teachers.annotate(
		merit_count=Count('behavior_entries', filter=Q(behavior_entries__entry_type='merit'), distinct=True),
	)
	teachers = teachers.prefetch_related(
		Prefetch('behavior_entries', queryset=TeacherBehavior.objects.order_by('-created_at'))
	)

	paginator = Paginator(teachers, page_size)
	page_number = request.GET.get('page')
	page_obj = paginator.get_page(page_number)

	context = {
		'q': q,
		'page_obj': page_obj,
		'page_size': page_size,
	}
	return render(request, 'core/teacher_list.html', context)


def student_behavior_add(request):
	"""ثبت تخلف یا امتیاز برای دانش‌آموز."""
	if request.method != 'POST':
		return redirect(reverse('core:student_list'))
	student_id = request.POST.get('student_id')
	entry_type = request.POST.get('entry_type')
	note = (request.POST.get('note') or '').strip()
	next_url = request.POST.get('next') or reverse('core:student_list')
	if entry_type not in ('violation', 'merit'):
		messages.error(request, 'نوع ثبت نامعتبر است.')
		return redirect(next_url)
	student = get_object_or_404(Student, pk=student_id)
	StudentBehavior.objects.create(student=student, entry_type=entry_type, note=note)
	if entry_type == 'violation':
		messages.success(request, 'تخلف دانش‌آموز ثبت شد.')
	else:
		messages.success(request, 'امتیاز دانش‌آموز ثبت شد.')
	return redirect(next_url)


def teacher_behavior_add(request):
	"""ثبت تخلف یا امتیاز برای استاد."""
	if request.method != 'POST':
		return redirect(reverse('core:teacher_list'))
	teacher_id = request.POST.get('teacher_id')
	entry_type = request.POST.get('entry_type')
	note = (request.POST.get('note') or '').strip()
	next_url = request.POST.get('next') or reverse('core:teacher_list')
	if entry_type not in ('violation', 'merit'):
		messages.error(request, 'نوع ثبت نامعتبر است.')
		return redirect(next_url)
	teacher = get_object_or_404(Teacher, pk=teacher_id)
	TeacherBehavior.objects.create(teacher=teacher, entry_type=entry_type, note=note)
	if entry_type == 'violation':
		messages.success(request, 'تخلف استاد ثبت شد.')
	else:
		messages.success(request, 'امتیاز استاد ثبت شد.')
	return redirect(next_url)


def student_behavior_update(request, pk):
	if request.method != 'POST':
		return JsonResponse({'ok': False}, status=405)
	entry = get_object_or_404(StudentBehavior, pk=pk)
	note = (request.POST.get('note') or '').strip()
	entry.note = note
	entry.save(update_fields=['note'])
	return JsonResponse({'ok': True, 'note': entry.note})


def student_behavior_delete(request, pk):
	if request.method != 'POST':
		return JsonResponse({'ok': False}, status=405)
	entry = get_object_or_404(StudentBehavior, pk=pk)
	entry.delete()
	return JsonResponse({'ok': True})


def teacher_behavior_update(request, pk):
	if request.method != 'POST':
		return JsonResponse({'ok': False}, status=405)
	entry = get_object_or_404(TeacherBehavior, pk=pk)
	note = (request.POST.get('note') or '').strip()
	entry.note = note
	entry.save(update_fields=['note'])
	return JsonResponse({'ok': True, 'note': entry.note})


def teacher_behavior_delete(request, pk):
	if request.method != 'POST':
		return JsonResponse({'ok': False}, status=405)
	entry = get_object_or_404(TeacherBehavior, pk=pk)
	entry.delete()
	return JsonResponse({'ok': True})


def teacher_plan_upload(request, pk):
	"""Upload a teacher's lesson plan PDF."""
	teacher = get_object_or_404(Teacher, pk=pk)
	if request.method != 'POST':
		return redirect(reverse('core:teacher_list'))
	plan_file = request.FILES.get('plan_file')
	next_url = request.POST.get('next') or reverse('core:teacher_list')
	if not plan_file:
		messages.error(request, 'لطفاً فایل پلان درسی را انتخاب کنید.')
		return redirect(next_url)
	if not plan_file.name.lower().endswith('.pdf'):
		messages.error(request, 'فایل پلان درسی باید PDF باشد.')
		return redirect(next_url)
	teacher.plan_file = plan_file
	teacher.save(update_fields=['plan_file'])
	messages.success(request, 'پلان درسی با موفقیت اپلود شد.')
	return redirect(next_url)


def teacher_plan_download(request, pk):
	"""Download a teacher's lesson plan PDF."""
	teacher = get_object_or_404(Teacher, pk=pk)
	if not teacher.plan_file:
		raise Http404()
	filename = os.path.basename(teacher.plan_file.name)
	response = FileResponse(teacher.plan_file.open('rb'), as_attachment=True, filename=filename)
	return response


def student_appreciation_print(request, pk):
	student = get_object_or_404(Student, pk=pk)
	merit_count = StudentBehavior.objects.filter(student=student, entry_type='merit').count()
	if merit_count < 3:
		raise Http404()
	today = timezone.now().date()
	jy, jm, jd = _gregorian_to_jalali(today.year, today.month, today.day)
	return render(request, 'core/student_appreciation_print.html', {
		'student': student,
		'jalali_date': f"{jy:04d}-{jm:02d}-{jd:02d}",
	})


def teacher_appreciation_print(request, pk):
	teacher = get_object_or_404(Teacher, pk=pk)
	merit_count = TeacherBehavior.objects.filter(teacher=teacher, entry_type='merit').count()
	if merit_count < 3:
		raise Http404()
	today = timezone.now().date()
	jy, jm, jd = _gregorian_to_jalali(today.year, today.month, today.day)
	return render(request, 'core/teacher_appreciation_print.html', {
		'teacher': teacher,
		'jalali_date': f"{jy:04d}-{jm:02d}-{jd:02d}",
	})


def teacher_create(request):
	"""Create a new Teacher. Classes and subjects are provided as searchable tags from frontend."""
	_ensure_reference_data()
	if request.method == 'POST':
		form = TeacherForm(request.POST, request.FILES)
		if form.is_valid():
			teacher = form.save()
			# process classes and subjects provided as repeated fields
			class_names = request.POST.getlist('classes')
			subject_names = request.POST.getlist('subjects')
			semester_values = request.POST.getlist('semesters')
			level_values = request.POST.getlist('levels')
			period_values = request.POST.getlist('periods_ebtedai') + request.POST.getlist('periods_moteseta')
			if class_names:
				classes_qs = SchoolClass.objects.filter(name__in=class_names)
				teacher.classes.set(classes_qs)
			if subject_names:
				sub_qs = Subject.objects.filter(name__in=subject_names)
				teacher.subjects.set(sub_qs)
			# handle semesters: create/get Semester objects for given numbers
			if semester_values:
				sem_qs = []
				for s in semester_values:
					s_norm = _persian_to_ascii(s.strip())
					try:
						num = int(s_norm)
					except ValueError:
						continue
					sem, _ = Semester.objects.get_or_create(number=num)
					sem_qs.append(sem)
				teacher.semesters.set(sem_qs)
			# handle levels
			if level_values is not None:
				level_qs = StudyLevel.objects.filter(code__in=level_values)
				teacher.levels.set(level_qs)
			# handle periods
			if period_values:
				period_qs = []
				for s in sorted(set(period_values)):
					s_norm = _persian_to_ascii(s.strip())
					try:
						num = int(s_norm)
					except ValueError:
						continue
					per, _ = CoursePeriod.objects.get_or_create(number=num)
					period_qs.append(per)
				teacher.periods.set(period_qs)
			messages.success(request, 'استاد با موفقیت ثبت شد.')
			return redirect(reverse('core:teacher_list'))
	else:
		form = TeacherForm()

	class_names = list(SchoolClass.objects.values_list('name', flat=True))
	subject_names = list(Subject.objects.values_list('name', flat=True))
	semester_names = [{'value': str(s.number), 'label': str(s)} for s in Semester.objects.order_by('number')]
	level_names = [{'value': l.code, 'label': l.name} for l in StudyLevel.objects.order_by('id')]
	period_names = [{'value': str(p.number), 'label': str(p)} for p in CoursePeriod.objects.order_by('number')]
	return render(request, 'core/teacher_form.html', {
		'form': form,
		'class_names': class_names,
		'subject_names': subject_names,
		'semester_names': semester_names,
		'level_names': level_names,
		'period_names': period_names,
	})


def teacher_edit(request, pk):
	_ensure_reference_data()
	teacher = get_object_or_404(Teacher, pk=pk)
	if request.method == 'POST':
		form = TeacherForm(request.POST, request.FILES, instance=teacher)
		if form.is_valid():
			teacher = form.save()
			class_names = request.POST.getlist('classes')
			subject_names = request.POST.getlist('subjects')
			semester_values = request.POST.getlist('semesters')
			level_values = request.POST.getlist('levels')
			period_values = request.POST.getlist('periods_ebtedai') + request.POST.getlist('periods_moteseta')
			if class_names is not None:
				classes_qs = SchoolClass.objects.filter(name__in=class_names)
				teacher.classes.set(classes_qs)
			if subject_names is not None:
				sub_qs = Subject.objects.filter(name__in=subject_names)
				teacher.subjects.set(sub_qs)
			if semester_values is not None:
				sem_qs = []
				for s in semester_values:
					s_norm = _persian_to_ascii(s.strip())
					try:
						num = int(s_norm)
					except ValueError:
						continue
					sem, _ = Semester.objects.get_or_create(number=num)
					sem_qs.append(sem)
				teacher.semesters.set(sem_qs)
			if level_values is not None:
				level_qs = StudyLevel.objects.filter(code__in=level_values)
				teacher.levels.set(level_qs)
			if period_values is not None:
				period_qs = []
				for s in sorted(set(period_values)):
					s_norm = _persian_to_ascii(s.strip())
					try:
						num = int(s_norm)
					except ValueError:
						continue
					per, _ = CoursePeriod.objects.get_or_create(number=num)
					period_qs.append(per)
				teacher.periods.set(period_qs)
			messages.success(request, 'اطلاعات استاد با موفقیت بروزرسانی شد.')
			return redirect(reverse('core:teacher_list'))
	else:
		form = TeacherForm(instance=teacher)

	class_names = list(SchoolClass.objects.values_list('name', flat=True))
	subject_names = list(Subject.objects.values_list('name', flat=True))
	# current selections to prefill tags
	teacher_classes = list(teacher.classes.values_list('name', flat=True))
	teacher_subjects = list(teacher.subjects.values_list('name', flat=True))
	teacher_semesters = list(teacher.semesters.values_list('number', flat=True))
	teacher_levels = list(teacher.levels.values_list('code', flat=True))
	teacher_periods = list(teacher.periods.values_list('number', flat=True))
	teacher_periods_ebtedai = []
	teacher_periods_moteseta = []
	if 'ebtedai' in teacher_levels:
		teacher_periods_ebtedai = [str(p) for p in teacher_periods]
	if 'moteseta' in teacher_levels:
		teacher_periods_moteseta = [str(p) for p in teacher_periods]
	semester_names = [{'value': str(s.number), 'label': str(s)} for s in Semester.objects.order_by('number')]
	level_names = [{'value': l.code, 'label': l.name} for l in StudyLevel.objects.order_by('id')]
	period_names = [{'value': str(p.number), 'label': str(p)} for p in CoursePeriod.objects.order_by('number')]
	return render(request, 'core/teacher_form.html', {
		'form': form,
		'class_names': class_names,
		'subject_names': subject_names,
		'teacher_classes': teacher_classes,
		'teacher_subjects': teacher_subjects,
		'teacher_semesters': [str(s) for s in teacher_semesters],
		'semester_names': semester_names,
		'level_names': level_names,
		'period_names': period_names,
		'teacher_levels': teacher_levels,
		'teacher_periods_ebtedai': teacher_periods_ebtedai,
		'teacher_periods_moteseta': teacher_periods_moteseta,
	})


def teacher_contract(request, pk):
	"""Create/update a teacher contract and allow generating a PDF."""
	teacher = get_object_or_404(Teacher, pk=pk)
	contract, _ = TeacherContract.objects.get_or_create(teacher=teacher)
	if not contract.contract_number:
		contract.save()

	if request.method == 'POST':
		form = TeacherContractForm(request.POST, request.FILES, instance=contract)
		if form.is_valid():
			form.save()
			messages.success(request, 'قرارداد استاد با موفقیت ذخیره شد.')
			return redirect(reverse('core:teacher_contract', args=[teacher.pk]))
	else:
		form = TeacherContractForm(instance=contract)

	teacher_subjects = ', '.join(teacher.subjects.values_list('name', flat=True)) or '—'
	teacher_classes = ', '.join(teacher.classes.values_list('name', flat=True)) or '—'
	teacher_levels = ', '.join(teacher.levels.values_list('name', flat=True)) or '—'
	teacher_semesters = teacher.get_persian_semesters() or '—'
	teacher_periods = ' '.join(str(p) for p in teacher.periods.order_by('number')) or '—'
	default_terms = (
		' اين قرار داد درتاريخ ([[contract_date]]) با رعايت اصول و اساسنامه دارالعلوم عالی الحاج سیّد منصور نادری فی مابین دارالعلــوم و آقای/خانم ([[teacher_name]]) فـــــرزند ([[father_name]]) مسکــونه اصلی قـــریه ([[permanent_village]]) ولسوالی ([[permanent_district]]) ولایت ([[permanent_province]]) مسکونه فعلی ناحیه ([[current_area]]) ولایت ([[current_province]]) دارنده شماره تذکره ([[id_number]]) دارای درجه تحصیلی ([[education_level]]) به عنوان استاد ([[position]])    برای مدت نه ماه، از ماه حمل الی ماه قوس سال [[current_year]] منعقد گردید، طرفين قانوناً و شرعاً خود را ملزم و متعهد به رعايت دقيق مفاد آن بشرح ذيل مي دانند. \n'
		'تعهدات استاد:\n'
		'    1. استاد باید اساسنامه، مقررات  و لوایح مربوطه دارالعلوم را رعایت نموده و با اخلاص، صداقت، و حسن نیت به تدریس خویش ادامه دهد.\n'
		'    2. استاد با توجه به خصوصیات مضمون مورد نظر به تهیه لکچر نوت، رهنمایی عملی پرداخته و در هنگام تدریس و کار با دانشجویان از روش های فعال، مناسب، معیاری، و عصری کار بگیرد. \n'
		'    3. استاد باید به طور منظم و در وقت معین طبق تقسیم اوقات و پلان درسی به تدریس خویش حاضر بوده و از ساعت 8:00 الی 4:00 بعد از ظهر  بطور کامل در تدریس و حل مشکلات درسی و اجرای کارخانگی به دانشجویان استفاده نماید. در صورت غیرحاضر بودن استاد در تدریس یومیه معاش یک روزه استاد قطع می گردد. \n'
		'    4. استاد مکلف به اخذ نمودن امتحان صنفی، وسط سمستر، نهایی، ارزیابی دانشجویان و سپری نمودن نتایج به موقع به اداره دارالعلوم می باشد.\n'
		'    5. استاد باید در تهیه سمینارها، کانفرانس ها، و نگارش منوگراف در موضوع اختصاصی دانشجویان را رهنمایی و کمک نماید.\n'
		'    6. استاد باید در هنگام اجرای وظیفه خویش از برخورد منفی، غیر علمی، منافی اصول نافذه، تبلیغات و فعالیت های سیاسی جداً اجتناب ورزد.\n'
		'    7. استاد بعد از امضای قرار داد نمیتواند در جریان سمستر قرار داد را فسخ نماید. و هرگاه خواهان فسخ آن باشد، اداره دارالعلوم را باید یک ماه قبل از ختم سمستر مطلع سازد و مکلف به تکمیل امتحانات و ارزیابی پارچه های سمستر جاری می باشد. در صورت ترک وظیفه بدون هماهنگی یک ماه قبل به اداره معاش یک ماه وی پرداخت نمی شود.\n'
		'    8. استاد در صورت مریضی و سایر مشکلات دیگر که عدم رسیدن به تدریس می شود، مکلف است که اداره دارالعلوم را یک روز پیش در جریان قرار بدهد.\n'
		'    9. استاد مکلف به تهیه مواد درسی هر سمستر طبق نصاب درسی دارالعلوم می باشد.\n'
		'    10. محل انجام کار دارالعلوم عالی الحاج سید منصورنادری بوده وساعت کاری از 8:00 صبح الی 4:00  شام می باشد.\n'
		'    11.  مدت این قرار داد نه ماه بوده از ماه حمل الی ماه قوس [[current_year]]، بعد از مدت معینه، در صورت درست انجام دادن وظیفه محوله دوباره تمدید می گردد.\n'
		'    12.  این قرار داد همان طور که در شماره فوق ذکر گردیده به مدت نه ماه بوده که بین اداره و استاد/ کارمند مربوطه عقد می گردد. و سه ماه زمستان بدون رخصتی مشروط به فعالیت و برگذاری مضمون استاد مربوطه می شود در غیر آن صورت اداره هیچ نوع مکلفیت به پرداخت معاش استاد/ کارمند مربوطه ندارد.\n'
		'    13.  استاد که قرار داد را امضاء می کند باید دارای درجه تحصیل لیسانس یا فارغ دارالعلوم عالی الحاج سید منصور نادری باشد و تعداد کریدیت مضمون مذکور نظر به نصاب درسی تعلیمات اسلامی و لزوم دید دارالعلوم تدریس می شود.\n'
		'    14.      رخصتی که اساتید از طرف اداره به هر مناسبتی ( عروسی، خرید، سفر و...) می گیرند هیچ ربطی به امتیاز ماهانه آن ندارد. و با اجازه گرفتن از طرف اداره فقط مکلفیت خویش را  نسبت به اداره  ادا نموده است\n'
		'    15.  این دارالعلوم از آن جهت که یک نهاد خصوصی و غیر انتفاعی بوده نمی تواند در رأس هر ماه معاش اساتید و کارمندان خود را پرداخت نماید و احتمال تأخیری در پرداخت معاش ماهوار وجود دارد.\n'
		'    16. استاد مربوطه مکلف به ساخت پلان درسی مضمون خویش بوده و باید مطابق پلان درسی در صنف حضور پیدا کرده و تدریس نماید.\n'
		'    17. برای اساتید که مسؤولیت تدریس مضامین فقه، حدیث، تفسیر و عقاید را به عهده دارد. با توجه به ضرورت و پیوند عمیق این مضامین با ادبیات عرب(صرف ونحو) برای اساتید متذکره فراگیری مضمون ادبیات عرب وعبور موفقانه از امتحان آن الزامی می باشد. تاریخ اخذ امتحان مضمون صرف اول سنبله و امتحان نحو مقدماتی اول قوس اخذ می گردد. \n'
		'تعهدات دارالعلوم:\n'
		'    1. پرداخت حق الزحمه مبلغ [[monthly_salary]] افغانی ماهانه.\n'
		'    2. دارالعلوم با در نظر داشت امكانات موجود، زمینه استفاده از کتابخانه، کمپیوتر و انترنت غرض تهیه مواد درسی مساعد می سازد. \n'
		'شرايط فسخ قرارداد:  دارالعلوم میتواند روی اسباب ذیل، این قرار داد را یک طرفه فسخ نماید.\n'
		'    ا. غیاب بیش تر از سه روز پی در پی  .\n'
		'    ب. عدم علاقه مندی و اهمال در وظیفه محوله .\n'
		'    ج. عدم موفقیت در  وظیفه محوله.\n'
		'    د. نارضایتی دانشجویان در صورت معیاری نبودن تدریس استاد.\n'
		'    ه. عدم پایبندی به بند های این قرار داد.\n'
		'    و. هر نوع حرکت غیر موازین اخلاقی و معرفی  شدن به عنوان اخلال گر.\n'
		'    ز. اداره به اساس اصول و پالیسی که در زمینه بهبود تدریس دارد سال یک بار اساتید خویش را ارزیابی می کند و در صورت عدم کسب رضایت شاگردان از استاد مربوطه این قرار داد از طرف اداره فسخ می شود.\n'
		'اين قرار داد در دو نسخه تنظيم مي شود كه يك نسخه در دارالعلوم ، يك نسخه نزد استاد و یا کارمند می باشند.\n'
		'                                                                     اسناد مطلوب از کارمند\n'
		'    • كاپی نسخه به استاد / کارمند                                      کاپی اسناد تحصیلی و تجارب کاری \n'
		'    • اصل نسخه به مدیریت اداری                                       کاپی تذکره          \n'
		'\t               \n'
	)

	return render(request, 'core/teacher_contract.html', {
		'teacher': teacher,
		'form': form,
		'contract': contract,
		'teacher_subjects': teacher_subjects,
		'teacher_classes': teacher_classes,
		'teacher_levels': teacher_levels,
		'teacher_semesters': teacher_semesters,
		'teacher_periods': teacher_periods,
		'default_terms': default_terms,
	})


def teacher_delete(request, pk):
	teacher = get_object_or_404(Teacher, pk=pk)
	if request.method == 'POST':
		teacher.delete()
		messages.success(request, 'استاد حذف شد.')
		return redirect(reverse('core:teacher_list'))
	return render(request, 'core/class_confirm_delete.html', {'klass': teacher})


def logo(request):
	"""Serve the app logo stored at core/images/logo.jpg during development.

	This keeps the template simple and doesn't require moving files into
	the static directory. In production, serve static assets via a proper
	static server and remove this view.
	"""
	logo_path = os.path.join(settings.BASE_DIR, 'core', 'images', 'logo.jpg')
	if not os.path.exists(logo_path):
		raise Http404('Logo not found')
	return FileResponse(open(logo_path, 'rb'), content_type='image/jpeg')


def emirate_logo(request):
	"""Serve the emirate logo stored at core/images/emirate.png during development."""
	logo_path = os.path.join(settings.BASE_DIR, 'core', 'images', 'emirate.png')
	if not os.path.exists(logo_path):
		raise Http404('Emirate logo not found')
	return FileResponse(open(logo_path, 'rb'), content_type='image/png')


def subject_list(request):
	"""Display list of subjects with search and pagination similar to students list."""
	level_map = _ensure_reference_data()
	level_param = request.GET.get('level', '').strip()
	if level_param not in level_map:
		level_param = 'aali'
	q = request.GET.get('q', '').strip()
	page_size_raw = request.GET.get('page_size', '20')
	allowed_page_sizes = {10, 20, 50, 100}
	try:
		page_size = int(page_size_raw)
	except (TypeError, ValueError):
		page_size = 20
	if page_size not in allowed_page_sizes:
		page_size = 20
	subjects = Subject.objects.all().order_by('-created_at')
	if level_param in level_map:
		level_obj = level_map[level_param]
		if level_obj.code == 'aali':
			subjects = subjects.filter(Q(level=level_obj) | Q(level__isnull=True))
		else:
			subjects = subjects.filter(level=level_obj)
	if q:
		subjects = subjects.filter(name__icontains=q)

	paginator = Paginator(subjects, page_size)
	page_number = request.GET.get('page')
	page_obj = paginator.get_page(page_number)

	context = {
		'q': q,
		'page_obj': page_obj,
		'selected_level': level_param,
		'page_size': page_size,
	}
	return render(request, 'core/subject_list.html', context)


def subject_create(request):
	"""Create a new Subject (مضمون)."""
	level_map = _ensure_reference_data()
	if request.method == 'POST':
		form = SubjectForm(request.POST)
		if form.is_valid():
			form.save()
			messages.success(request, 'مضمون با موفقیت ثبت شد.')
			return redirect(reverse('core:subject_list'))
	else:
		form = SubjectForm()
	level_ids = {k: v.id for k, v in level_map.items()}
	return render(request, 'core/subject_form.html', {'form': form, 'level_ids': level_ids})


def subject_edit(request, pk):
	"""Edit an existing Subject."""
	level_map = _ensure_reference_data()
	subject = get_object_or_404(Subject, pk=pk)
	if request.method == 'POST':
		form = SubjectForm(request.POST, instance=subject)
		if form.is_valid():
			form.save()
			messages.success(request, 'اطلاعات مضمون با موفقیت بروزرسانی شد.')
			return redirect(reverse('core:subject_list'))
	else:
		form = SubjectForm(instance=subject)
	level_ids = {k: v.id for k, v in level_map.items()}
	return render(request, 'core/subject_form.html', {'form': form, 'level_ids': level_ids})


def subject_delete(request, pk):
	"""Delete a Subject (POST only to perform deletion)."""
	subject = get_object_or_404(Subject, pk=pk)
	if request.method == 'POST':
		subject.delete()
		messages.success(request, 'مضمون حذف شد.')
		return redirect(reverse('core:subject_list'))
	return render(request, 'core/class_confirm_delete.html', {'klass': subject})


def classes_list(request):
	"""Display list of classes with search and pagination similar to students list.

	If there is no SchoolClass data yet, the page will show empty state (and the
	"+ افزودن صنف جدید" button still allows creating new classes).
	"""
	level_map = _ensure_reference_data()
	level_param = request.GET.get('level', '').strip()
	if level_param not in level_map:
		level_param = 'aali'
	q = request.GET.get('q', '').strip()
	page_size_raw = request.GET.get('page_size', '20')
	allowed_page_sizes = {10, 20, 50, 100}
	try:
		page_size = int(page_size_raw)
	except (TypeError, ValueError):
		page_size = 20
	if page_size not in allowed_page_sizes:
		page_size = 20
	classes = SchoolClass.objects.all().order_by('-created_at')
	if level_param in level_map:
		classes = classes.filter(level=level_map[level_param])
	if q:
		classes = classes.filter(name__icontains=q)

	paginator = Paginator(classes, page_size)
	page_number = request.GET.get('page')
	page_obj = paginator.get_page(page_number)

	context = {
		'q': q,
		'page_obj': page_obj,
		'selected_level': level_param,
		'page_size': page_size,
	}
	return render(request, 'core/classes_list.html', context)


def class_create(request):
	"""Create a new SchoolClass."""
	level_map = _ensure_reference_data()
	if request.method == 'POST':
		form = SchoolClassForm(request.POST)
		if form.is_valid():
			klass = form.save()
			# handle semester value (posted as repeated/hidden field named 'semester')
			sem_val = request.POST.get('semester')
			per_val = request.POST.get('period')
			level_code = klass.level.code if klass.level else ''
			if level_code == 'aali' and sem_val:
				# convert Persian digits to ascii if necessary
				def persian_to_ascii(s: str) -> str:
					mapping = {'۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9'}
					return ''.join(mapping.get(ch, ch) for ch in s)
				try:
					num = int(persian_to_ascii(sem_val.strip()))
				except Exception:
					num = None
				if num is not None:
					sem_obj, _ = Semester.objects.get_or_create(number=num)
					klass.semester = sem_obj
			else:
				klass.semester = None
			if level_code in ('ebtedai', 'moteseta') and per_val:
				try:
					per_id = int(_persian_to_ascii(per_val.strip()))
				except Exception:
					per_id = None
				if per_id is not None:
					klass.period = CoursePeriod.objects.filter(id=per_id).first()
			else:
				klass.period = None
			klass.save()
			messages.success(request, 'صنف با موفقیت ثبت شد.')
			return redirect(reverse('core:classes_list'))
	else:
		form = SchoolClassForm()
	# provide existing semesters from DB so frontend can show them
	semester_qs = Semester.objects.order_by('number')
	semester_names = [{'value': str(s.number), 'label': str(s)} for s in semester_qs]
	period_qs = CoursePeriod.objects.order_by('number')
	period_names = [{'value': str(p.id), 'label': str(p)} for p in period_qs]
	level_ids = {k: v.id for k, v in level_map.items()}
	return render(request, 'core/class_form.html', {
		'form': form,
		'semester_names': semester_names,
		'period_names': period_names,
		'level_ids': level_ids,
	})


def class_edit(request, pk):
	"""Edit an existing SchoolClass."""
	level_map = _ensure_reference_data()
	klass = get_object_or_404(SchoolClass, pk=pk)
	if request.method == 'POST':
		form = SchoolClassForm(request.POST, instance=klass)
		if form.is_valid():
			klass = form.save()
			sem_val = request.POST.get('semester')
			per_val = request.POST.get('period')
			level_code = klass.level.code if klass.level else ''
			if level_code == 'aali' and sem_val is not None:
				def persian_to_ascii(s: str) -> str:
					mapping = {'۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9'}
					return ''.join(mapping.get(ch, ch) for ch in s)
				try:
					num = int(persian_to_ascii(sem_val.strip()))
				except Exception:
					num = None
				if num is not None:
					sem_obj, _ = Semester.objects.get_or_create(number=num)
					klass.semester = sem_obj
				else:
					klass.semester = None
			else:
				klass.semester = None
			if level_code in ('ebtedai', 'moteseta') and per_val is not None:
				try:
					per_id = int(_persian_to_ascii(per_val.strip()))
				except Exception:
					per_id = None
				if per_id is not None:
					klass.period = CoursePeriod.objects.filter(id=per_id).first()
				else:
					klass.period = None
			else:
				klass.period = None
			klass.save()
			messages.success(request, 'اطلاعات صنف با موفقیت بروزرسانی شد.')
			return redirect(reverse('core:classes_list'))
	else:
		form = SchoolClassForm(instance=klass)
	semester_qs = Semester.objects.order_by('number')
	semester_names = [{'value': str(s.number), 'label': str(s)} for s in semester_qs]
	period_qs = CoursePeriod.objects.order_by('number')
	period_names = [{'value': str(p.id), 'label': str(p)} for p in period_qs]
	selected_semester = str(klass.semester.number) if klass.semester else ''
	selected_period = str(klass.period.id) if klass.period else ''
	level_ids = {k: v.id for k, v in level_map.items()}
	return render(request, 'core/class_form.html', {
		'form': form,
		'semester_names': semester_names,
		'period_names': period_names,
		'selected_semester': selected_semester,
		'selected_period': selected_period,
		'level_ids': level_ids,
	})


def class_delete(request, pk):
	"""Delete a SchoolClass. Accept POST only to perform deletion."""
	klass = get_object_or_404(SchoolClass, pk=pk)
	if request.method == 'POST':
		klass.delete()
		messages.success(request, 'صنف حذف شد.')
		return redirect(reverse('core:classes_list'))
	return render(request, 'core/class_confirm_delete.html', {'klass': klass})


def student_edit(request, pk):
	"""Edit an existing student."""
	level_map = _ensure_reference_data()
	student = get_object_or_404(Student, pk=pk)
	if request.method == 'POST':
		form = StudentForm(request.POST, request.FILES, instance=student)
		if form.is_valid():
			form.save()
			messages.success(request, 'اطلاعات دانش‌آموز با موفقیت بروزرسانی شد.')
			return redirect(reverse('core:student_list'))
	else:
		form = StudentForm(instance=student)
	level_ids = {k: v.id for k, v in level_map.items()}
	period_names = [{'value': str(p.id), 'label': str(p)} for p in CoursePeriod.objects.order_by('number')]
	student_periods_ebtedai = []
	student_periods_moteseta = []
	if student.level:
		if student.level.code == 'ebtedai':
			student_periods_ebtedai = [str(p.id) for p in student.periods.all()]
		elif student.level.code == 'moteseta':
			student_periods_moteseta = [str(p.id) for p in student.periods.all()]
	return render(request, 'core/student_form_clean.html', {
		'form': form,
		'level_ids': level_ids,
		'period_names': period_names,
		'student_periods_ebtedai': student_periods_ebtedai,
		'student_periods_moteseta': student_periods_moteseta,
	})


def student_delete(request, pk):
	"""Delete a student. Only accept POST to perform deletion."""
	student = get_object_or_404(Student, pk=pk)
	if request.method == 'POST':
		student.delete()
		messages.success(request, 'دانش‌آموز حذف شد.')
		return redirect(reverse('core:student_list'))
	# If GET, render a very small confirmation page (fallback) to avoid accidental deletes
	return render(request, 'core/student_confirm_delete.html', {'student': student})


def dashboard(request):
	"""Dashboard view showing totals and a pie chart of students per class."""
	level_map = _ensure_reference_data()
	total_students = Student.objects.count()
	total_teachers = Teacher.objects.count()
	total_subjects = Subject.objects.count()
	total_classes = SchoolClass.objects.count()

	# Pie chart: students by gender (مذکر/مونث)
	male_total = Student.objects.filter(gender='male').count()
	female_total = Student.objects.filter(gender='female').count()
	chart_json = mark_safe(json.dumps({
		'labels': ['مذکر', 'مونث'],
		'data': [male_total, female_total],
	}))
	# Bar chart: students by level (عالی/متوسطه/ابتداییه)
	level_order = ['aali', 'moteseta', 'ebtedai']
	level_labels = [level_map[k].name for k in level_order if k in level_map]
	level_counts = []
	for k in level_order:
		if k not in level_map:
			continue
		lv = level_map[k]
		level_counts.append(
			Student.objects.filter(Q(level=lv) | Q(level__isnull=True, school_class__level=lv)).count()
		)
	level_chart_json = mark_safe(json.dumps({'labels': level_labels, 'data': level_counts}))

	context = {
		'total_students': total_students,
		'total_teachers': total_teachers,
		'total_subjects': total_subjects,
		'total_classes': total_classes,
		'chart_json': chart_json,
		'level_chart_json': level_chart_json,
	}
	return render(request, 'core/dashboard.html', context)


def grade_entry(request):
	"""Enter or update grades for a selected student across multiple subjects.

	Frontend sends `student_id`, and arrays `subject_ids[]` and `scores[]`.
	"""
	students_qs = Student.objects.all().order_by('name')
	# include semesters list per student for client-side filtering
	# If the student object has no explicit semesters assigned, fall back to
	# the semester of the SchoolClass with matching `class_name`.
	students = []
	for s in students_qs:
		level_obj = s.level or (s.school_class.level if s.school_class else None)
		level_code = level_obj.code if level_obj else ''
		level_name = level_obj.name if level_obj else ''
		sems = list(s.semesters.values_list('number', flat=True)) if hasattr(s, 'semesters') else []
		periods = list(s.periods.values_list('number', flat=True)) if hasattr(s, 'periods') else []
		# fallback: determine semester from student's school_class -> SchoolClass.semester
		if not sems:
			try:
				if s.school_class and s.school_class.semester:
					sems = [s.school_class.semester.number]
			except Exception:
				# any unexpected issue -> keep sems empty
				sems = []
		if not periods:
			try:
				if s.school_class and s.school_class.period:
					periods = [s.school_class.period.number]
			except Exception:
				periods = []
		students.append({
			'id': s.id,
			'display': f"{s.name} ({s.father_name})",
			'semesters': sems,
			'periods': periods,
			'level_code': level_code,
			'level_name': level_name,
			'class_name': s.school_class.name if s.school_class else ''
		})

	subjects_qs = Subject.objects.order_by('name')
	# include semester/period/level for each subject
	subjects = [
		{
			'id': sub.id,
			'name': sub.name,
			'semester': sub.semester,
			'period': sub.period.number if sub.period else None,
			'level_code': sub.level.code if sub.level else '',
			'level_name': sub.level.name if sub.level else ''
		}
		for sub in subjects_qs
	]

	if request.method == 'POST':
		student_id = request.POST.get('student_id')
		if not student_id:
			messages.error(request, 'لطفاً یک دانش‌آموز انتخاب کنید.')
			return redirect(reverse('core:grade_entry'))
		try:
			student = Student.objects.get(pk=int(student_id))
		except (Student.DoesNotExist, ValueError):
			messages.error(request, 'دانش‌آموز انتخاب شده نامعتبر است.')
			return redirect(reverse('core:grade_entry'))

		subject_ids = request.POST.getlist('subject_ids[]') or request.POST.getlist('subject_ids')
		scores = request.POST.getlist('scores[]') or request.POST.getlist('scores')

		created = 0
		updated = 0
		errors = 0
		saved_subjects = []  # collect saved subjects info to show on page
		# Pair up subject_ids and scores by index
		for idx, sid in enumerate(subject_ids):
			try:
				sub_id = int(sid)
			except ValueError:
				errors += 1
				continue
			score_val = None
			if idx < len(scores):
				val = scores[idx].strip()
				try:
					if val == '':
						score_val = None
					else:
						score_val = int(val)
						if score_val < 0 or score_val > 100:
							raise ValueError('score out of range')
				except Exception:
					errors += 1
					continue

			# Try to fetch subject name for display
			try:
				subj = Subject.objects.get(pk=sub_id)
			except Subject.DoesNotExist:
				errors += 1
				continue

			obj, created_flag = StudentScore.objects.update_or_create(
				student=student, subject_id=sub_id,
				defaults={'score': score_val}
			)
			if created_flag:
				created += 1
				op = 'created'
			else:
				updated += 1
				op = 'updated'

			saved_subjects.append({'id': sub_id, 'name': subj.name, 'score': score_val, 'op': op})

		# Auto-promotion logic: if student passed all subjects in current term, advance term or mark graduated
		promotion_message = _auto_promote_student(student)
		if promotion_message:
			messages.info(request, promotion_message)

		# Do not redirect: render the form again and show which subjects were saved
		messages.success(request, f'عملیات ثبت نمرات انجام شد. ایجاد: {created} — بروزرسانی: {updated} — خطاها: {errors}')
		# Rebuild subjects list for template render (same as GET below)
		subjects_qs = Subject.objects.order_by('name')
		subjects = [
			{
				'id': sub.id,
				'name': sub.name,
				'semester': sub.semester,
				'period': sub.period.number if sub.period else None,
				'level_code': sub.level.code if sub.level else '',
				'level_name': sub.level.name if sub.level else ''
			}
			for sub in subjects_qs
		]
		return render(request, 'core/grades_form.html', {'students': students, 'subjects': subjects, 'saved_subjects': saved_subjects, 'saved_student_id': student.id})

	# GET
	return render(request, 'core/grades_form.html', {'students': students, 'subjects': subjects})


def _auto_promote_student(student):
	"""Promote student to next semester/period if all subjects in current term are passed.

	Returns a message string if promotion or graduation occurred, otherwise None.
	"""
	if getattr(student, 'is_graduated', False):
		return None

	level_obj = student.level or (student.school_class.level if student.school_class else None)
	level_map = _ensure_reference_data()

	latest_semester = student.semesters.order_by('-number').first() if student.semesters.exists() else None
	if not latest_semester and student.school_class and student.school_class.semester:
		latest_semester = student.school_class.semester
	latest_period = student.periods.order_by('-number').first() if student.periods.exists() else None
	if not latest_period and student.school_class and student.school_class.period:
		latest_period = student.school_class.period

	if not level_obj:
		if latest_semester and not latest_period:
			level_obj = level_map.get('aali')
		elif latest_period:
			level_obj = level_map.get('moteseta') or level_map.get('ebtedai')

	level_code = level_obj.code if level_obj else ''
	if not level_code:
		return None

	subjects = Subject.objects.none()
	if level_code == 'aali':
		if not latest_semester:
			return None
		subjects = Subject.objects.filter(semester=latest_semester.number)
		subjects = subjects.filter(Q(level=level_obj) | Q(level__isnull=True))
	elif level_code in ('moteseta', 'ebtedai'):
		if not latest_period:
			return None
		subjects = Subject.objects.filter(period=latest_period)
		if level_obj:
			subjects = subjects.filter(Q(level=level_obj) | Q(level__isnull=True))
	else:
		return None

	subjects = subjects.order_by('id')
	if not subjects.exists():
		return None

	scores_qs = StudentScore.objects.filter(student=student, subject__in=subjects)
	if scores_qs.count() < subjects.count():
		return None
	if scores_qs.filter(score__isnull=True).exists():
		return None
	if scores_qs.filter(score__lt=50).exists():
		return None

	# Passed all subjects, promote or graduate
	if level_code == 'aali':
		current = latest_semester.number
		if current >= 4:
			student.is_graduated = True
			student.save(update_fields=['is_graduated'])
			return 'دانش‌آموز فارغ شد.'
		next_sem = Semester.objects.filter(number=current + 1).first()
		if not next_sem:
			next_sem = Semester.objects.create(number=current + 1)
		student.semesters.set([next_sem])
		student.is_graduated = False
		student.save(update_fields=['is_graduated'])
		return f'دانش‌آموز به سمستر {current + 1} ارتقا یافت.'

	if level_code in ('moteseta', 'ebtedai'):
		current = latest_period.number
		if current >= 6:
			student.is_graduated = True
			student.save(update_fields=['is_graduated'])
			return 'دانش‌آموز فارغ شد.'
		next_period = CoursePeriod.objects.filter(number=current + 1).first()
		if not next_period:
			next_period = CoursePeriod.objects.create(number=current + 1)
		student.periods.set([next_period])
		student.is_graduated = False
		student.save(update_fields=['is_graduated'])
		return f'دانش‌آموز به دوره {current + 1} ارتقا یافت.'

	return None


def _can_graduate_ebtedai(student):
	"""Return (True, latest_period) if student passed all subjects in period 6 of ابتداییه."""
	level_obj = student.level or (student.school_class.level if student.school_class else None)
	level_map = _ensure_reference_data()
	if not level_obj:
		level_obj = level_map.get('ebtedai')
	if not level_obj or level_obj.code != 'ebtedai':
		return False, None

	latest_period = student.periods.order_by('-number').first() if student.periods.exists() else None
	if not latest_period and student.school_class and student.school_class.period:
		latest_period = student.school_class.period
	if not latest_period or latest_period.number != 6:
		return False, latest_period

	subjects = Subject.objects.filter(period=latest_period)
	subjects = subjects.filter(Q(level=level_obj) | Q(level__isnull=True)).order_by('id')
	if not subjects.exists():
		return False, latest_period

	scores_qs = StudentScore.objects.filter(student=student, subject__in=subjects)
	if scores_qs.count() < subjects.count():
		return False, latest_period
	if scores_qs.filter(score__isnull=True).exists():
		return False, latest_period
	if scores_qs.filter(score__lt=50).exists():
		return False, latest_period

	return True, latest_period


def api_class_search(request):
	"""AJAX endpoint for searching SchoolClass by name.
	
	Only returns classes that match the search query.
	If no query provided, returns empty list.
	"""
	query = request.GET.get('q', '').strip()
	page = int(request.GET.get('page', 1))
	page_size = 20
	
	# IMPORTANT: Only search if query is provided
	# This ensures we NEVER load all classes
	if not query:
		# Return empty results if no search term
		return JsonResponse({
			'results': [],
			'pagination': {'more': False}
		})
	
	# Filter classes by name (case-insensitive) - ONLY matching classes
	classes = SchoolClass.objects.filter(name__icontains=query).order_by('name')
	
	# Paginate the filtered results
	start = (page - 1) * page_size
	end = start + page_size
	total_count = classes.count()
	classes_page = classes[start:end]
	
	# Format for Select2
	results = [
		{'id': c.id, 'text': c.name}
		for c in classes_page
	]
	
	return JsonResponse({
		'results': results,
		'pagination': {
			'more': end < total_count
		}
	})


def student_exam_results(request, pk):
	"""Display the latest exam results for a student in a printable format."""
	from datetime import datetime
	
	student = get_object_or_404(Student, pk=pk)
	level_obj = student.level or (student.school_class.level if student.school_class else None)
	
	# Get the latest semester for this student
	# First try from student's assigned semesters
	latest_semester = None
	latest_period = None
	if student.semesters.exists():
		latest_semester = student.semesters.order_by('-number').first()
	# Fallback to class semester
	elif student.school_class and student.school_class.semester:
		latest_semester = student.school_class.semester
	if student.periods.exists():
		latest_period = student.periods.order_by('-number').first()
	elif student.school_class and student.school_class.period:
		latest_period = student.school_class.period

	level_map = _ensure_reference_data()
	if not level_obj:
		if latest_period:
			moteseta_level = level_map.get('moteseta')
			ebtedai_level = level_map.get('ebtedai')
			if moteseta_level and Subject.objects.filter(period=latest_period, level=moteseta_level).exists():
				level_obj = moteseta_level
			elif ebtedai_level and Subject.objects.filter(period=latest_period, level=ebtedai_level).exists():
				level_obj = ebtedai_level
			else:
				level_obj = moteseta_level or ebtedai_level
		elif latest_semester and not latest_period:
			level_obj = level_map.get('aali')

	if level_obj:
		level_code = level_obj.code
		level_name = level_obj.name
	else:
		level_code = 'unknown'
		level_name = 'نامشخص'
	
	# Get all scores for subjects in the latest semester
	scores = []
	total_score = 0
	max_possible = 0
	subjects_count = 0
	
	subjects = Subject.objects.none()
	if level_code == 'aali':
		if latest_semester:
			subjects = Subject.objects.filter(semester=latest_semester.number)
		else:
			subjects = Subject.objects.all()
		if level_obj:
			subjects = subjects.filter(Q(level=level_obj) | Q(level__isnull=True))
		subjects = subjects.order_by('name')
	elif level_code in ('moteseta', 'ebtedai'):
		if latest_period:
			subjects = Subject.objects.filter(period=latest_period)
			if level_obj:
				subjects = subjects.filter(Q(level=level_obj) | Q(level__isnull=True))
		else:
			subjects = Subject.objects.filter(level=level_obj) if level_obj else Subject.objects.filter(level__code=level_code)
		subjects = subjects.order_by('name')
	else:
		subjects = Subject.objects.all().order_by('name')

	for subject in subjects:
		# Try to get the score for this student and subject
		try:
			student_score = StudentScore.objects.get(student=student, subject=subject)
			score_value = student_score.score if student_score.score is not None else 0
		except StudentScore.DoesNotExist:
			score_value = 0

		score_value = max(0, min(score_value, 100))
		status = 'کامیاب' if score_value >= 50 else 'ناکام'

		scores.append({
			'subject_name': subject.name,
			'score': score_value,
			'status': status
		})

		total_score += score_value
		subjects_count += 1
		max_possible += 100
	
	# Calculate percentage and average
	percentage = (total_score / max_possible * 100) if max_possible > 0 else 0
	average = (total_score / subjects_count) if subjects_count > 0 else 0
	all_passed = all(item['score'] >= 50 for item in scores) if subjects_count > 0 else False
	overall_status = 'کامیاب' if all_passed else 'ناکام' if subjects_count > 0 else 'نامشخص'
	can_graduate_ebtedai = bool(all_passed and level_code == 'ebtedai' and latest_period and latest_period.number == 6)
	
	# Get current date for report
	current_date = datetime.now().strftime('%Y-%m-%d')
	term_label = 'سمستر' if level_code == 'aali' else 'دوره'
	term_value = latest_semester if level_code == 'aali' else latest_period
	if level_code == 'aali':
		sheet_title = 'پارچه امتحانات دوره عالی'
	elif level_code == 'moteseta':
		sheet_title = 'پارچه امتحانات دوره متوسطه'
	elif level_code == 'ebtedai':
		sheet_title = 'پارچه امتحانات دوره ابتداییه'
	else:
		sheet_title = 'پارچه امتحانات'

	term_value_display = 'فارغ' if getattr(student, 'is_graduated', False) else term_value

	context = {
		'student': student,
		'semester': latest_semester,
		'period': latest_period,
		'scores': scores,
		'total_score': total_score,
		'max_possible': max_possible,
		'percentage': round(percentage, 2),
		'average': round(average, 2),
		'overall_status': overall_status,
		'subjects_count': subjects_count,
		'current_date': current_date,
		'level_code': level_code,
		'level_name': level_name,
		'term_label': term_label,
		'term_value': term_value,
		'term_value_display': term_value_display,
		'sheet_title': sheet_title,
		'can_graduate_ebtedai': can_graduate_ebtedai,
	}
	
	return render(request, 'core/student_exam_results.html', context)


def student_certificate_print(request, pk):
	"""Printable certificate for completing ابتداییه period 6."""
	from datetime import datetime

	student = get_object_or_404(Student, pk=pk)
	can_graduate, latest_period = _can_graduate_ebtedai(student)
	if not can_graduate:
		messages.error(request, 'شرایط چاپ سرتفیکت ابتداییه تکمیل نیست.')
		return redirect(reverse('core:student_exam_results', args=[pk]))

	today = datetime.now()
	jy, jm, jd = _gregorian_to_jalali(today.year, today.month, today.day)
	current_date = f"{jy:04d}/{jm:02d}/{jd:02d}"
	context = {
		'student': student,
		'period': latest_period,
		'current_date': current_date,
	}
	return render(request, 'core/student_certificate.html', context)


@require_POST
def student_promote_to_moteseta(request, pk):
	"""Promote a student from ابتداییه period 6 to متوسطه period 1."""
	student = get_object_or_404(Student, pk=pk)
	can_graduate, _ = _can_graduate_ebtedai(student)
	if not can_graduate:
		messages.error(request, 'شرایط ارتقا به متوسطه تکمیل نیست.')
		return redirect(reverse('core:student_exam_results', args=[pk]))

	level_map = _ensure_reference_data()
	moteseta = level_map.get('moteseta')
	if not moteseta:
		messages.error(request, 'سطح متوسطه در سیستم تعریف نشده است.')
		return redirect(reverse('core:student_exam_results', args=[pk]))

	next_period = CoursePeriod.objects.filter(number=1).first()
	if not next_period:
		next_period = CoursePeriod.objects.create(number=1)

	student.level = moteseta
	student.school_class = None
	student.is_graduated = False
	student.save(update_fields=['level', 'school_class', 'is_graduated'])
	student.periods.set([next_period])
	student.semesters.clear()

	messages.success(request, 'دانش‌آموز به سطح متوسطه ارتقا یافت. لطفاً صنف جدید را انتخاب کنید.')
	return redirect(reverse('core:student_edit', args=[pk]))
