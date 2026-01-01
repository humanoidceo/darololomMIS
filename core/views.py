from django.shortcuts import render, redirect, get_object_or_404
from django.urls import reverse
from django.core.paginator import Paginator
from django.db.models import Q
from django.contrib import messages
from django.http import FileResponse, Http404
from django.conf import settings
import os
from .models import Student, SchoolClass, Subject, Teacher
from .forms import StudentForm, SchoolClassForm, SubjectForm, TeacherForm
from .models import StudentScore
import json
from django.utils.safestring import mark_safe


def student_create(request):
	if request.method == 'POST':
		form = StudentForm(request.POST, request.FILES)
		if form.is_valid():
			form.save()
			messages.success(request, 'دانش‌آموز با موفقیت ثبت شد.')
			return redirect(reverse('core:student_list'))
	else:
		form = StudentForm()
	# Pass available class names so the template can provide a searchable selector
	class_names = list(SchoolClass.objects.values_list('name', flat=True))
	return render(request, 'core/student_form_clean.html', {'form': form, 'class_names': class_names})


def student_list(request):
	"""نمایش لیست دانش‌آموزان با قابلیت جستجو و صفحه‌بندی (20 در هر صفحه)."""
	q = request.GET.get('q', '').strip()
	students = Student.objects.all().order_by('-created_at')
	if q:
		students = students.filter(
			Q(name__icontains=q) | Q(father_name__icontains=q) | Q(mobile_number__icontains=q)
		)

	paginator = Paginator(students, 20)
	page_number = request.GET.get('page')
	page_obj = paginator.get_page(page_number)

	context = {
		'q': q,
		'page_obj': page_obj,
	}
	return render(request, 'core/student_list.html', context)


def teacher_list(request):
	"""نمایش لیست اساتید مشابه لیست دانش‌آموزان با جستجو و صفحه‌بندی."""
	q = request.GET.get('q', '').strip()
	teachers = Teacher.objects.all().order_by('-created_at')
	if q:
		teachers = teachers.filter(
			Q(name__icontains=q) | Q(father_name__icontains=q) | Q(id_number__icontains=q)
		)

	paginator = Paginator(teachers, 20)
	page_number = request.GET.get('page')
	page_obj = paginator.get_page(page_number)

	context = {
		'q': q,
		'page_obj': page_obj,
	}
	return render(request, 'core/teacher_list.html', context)


def teacher_create(request):
	"""Create a new Teacher. Classes and subjects are provided as searchable tags from frontend."""
	if request.method == 'POST':
		form = TeacherForm(request.POST, request.FILES)
		if form.is_valid():
			teacher = form.save()
			# process classes and subjects provided as repeated fields
			class_names = request.POST.getlist('classes')
			subject_names = request.POST.getlist('subjects')
			semester_values = request.POST.getlist('semesters')
			if class_names:
				classes_qs = SchoolClass.objects.filter(name__in=class_names)
				teacher.classes.set(classes_qs)
			if subject_names:
				sub_qs = Subject.objects.filter(name__in=subject_names)
				teacher.subjects.set(sub_qs)
			# handle semesters: create/get Semester objects for given numbers
			if semester_values:
				from .models import Semester
				sem_qs = []
				def persian_to_ascii(s: str) -> str:
					# convert Persian digits to ascii digits if present
					mapping = {
						'۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
						'۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
					}
					return ''.join(mapping.get(ch, ch) for ch in s)

				for s in semester_values:
					s_norm = persian_to_ascii(s.strip())
					try:
						num = int(s_norm)
					except ValueError:
						continue
					sem, _ = Semester.objects.get_or_create(number=num)
					sem_qs.append(sem)
				teacher.semesters.set(sem_qs)
			messages.success(request, 'استاد با موفقیت ثبت شد.')
			return redirect(reverse('core:teacher_list'))
	else:
		form = TeacherForm()

	class_names = list(SchoolClass.objects.values_list('name', flat=True))
	subject_names = list(Subject.objects.values_list('name', flat=True))
	# semester options as objects with value (ascii) and label (persian)
	pers_map = {'0': '۰', '1': '۱', '2': '۲', '3': '۳', '4': '۴'}
	semester_names = [{'value': str(n), 'label': ''.join(pers_map.get(ch, ch) for ch in str(n))} for n in range(1, 5)]
	return render(request, 'core/teacher_form.html', {'form': form, 'class_names': class_names, 'subject_names': subject_names, 'semester_names': semester_names})


def teacher_edit(request, pk):
	teacher = get_object_or_404(Teacher, pk=pk)
	if request.method == 'POST':
		form = TeacherForm(request.POST, request.FILES, instance=teacher)
		if form.is_valid():
			teacher = form.save()
			class_names = request.POST.getlist('classes')
			subject_names = request.POST.getlist('subjects')
			semester_values = request.POST.getlist('semesters')
			if class_names is not None:
				classes_qs = SchoolClass.objects.filter(name__in=class_names)
				teacher.classes.set(classes_qs)
			if subject_names is not None:
				sub_qs = Subject.objects.filter(name__in=subject_names)
				teacher.subjects.set(sub_qs)
			if semester_values is not None:
				from .models import Semester
				sem_qs = []
				def persian_to_ascii(s: str) -> str:
					mapping = {'۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9'}
					return ''.join(mapping.get(ch, ch) for ch in s)

				for s in semester_values:
					s_norm = persian_to_ascii(s.strip())
					try:
						num = int(s_norm)
					except ValueError:
						continue
					sem, _ = Semester.objects.get_or_create(number=num)
					sem_qs.append(sem)
				teacher.semesters.set(sem_qs)
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
	pers_map = {'0': '۰', '1': '۱', '2': '۲', '3': '۳', '4': '۴'}
	semester_names = [{'value': str(n), 'label': ''.join(pers_map.get(ch, ch) for ch in str(n))} for n in range(1, 5)]
	return render(request, 'core/teacher_form.html', {
		'form': form,
		'class_names': class_names,
		'subject_names': subject_names,
		'teacher_classes': teacher_classes,
		'teacher_subjects': teacher_subjects,
		'teacher_semesters': [str(s) for s in teacher_semesters],
		'semester_names': semester_names,
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


def subject_list(request):
	"""Display list of subjects with search and pagination similar to students list.

	Supports simple name search via ?q= and pagination (20 per page).
	"""
	q = request.GET.get('q', '').strip()
	subjects = Subject.objects.all().order_by('-created_at')
	if q:
		subjects = subjects.filter(name__icontains=q)

	paginator = Paginator(subjects, 20)
	page_number = request.GET.get('page')
	page_obj = paginator.get_page(page_number)

	context = {
		'q': q,
		'page_obj': page_obj,
	}
	return render(request, 'core/subject_list.html', context)


def subject_create(request):
	"""Create a new Subject (مضمون)."""
	if request.method == 'POST':
		form = SubjectForm(request.POST)
		if form.is_valid():
			form.save()
			messages.success(request, 'مضمون با موفقیت ثبت شد.')
			return redirect(reverse('core:subject_list'))
	else:
		form = SubjectForm()
	return render(request, 'core/subject_form.html', {'form': form})


def subject_edit(request, pk):
	"""Edit an existing Subject."""
	subject = get_object_or_404(Subject, pk=pk)
	if request.method == 'POST':
		form = SubjectForm(request.POST, instance=subject)
		if form.is_valid():
			form.save()
			messages.success(request, 'اطلاعات مضمون با موفقیت بروزرسانی شد.')
			return redirect(reverse('core:subject_list'))
	else:
		form = SubjectForm(instance=subject)
	return render(request, 'core/subject_form.html', {'form': form})


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
	q = request.GET.get('q', '').strip()
	classes = SchoolClass.objects.all().order_by('-created_at')
	if q:
		classes = classes.filter(name__icontains=q)

	paginator = Paginator(classes, 20)
	page_number = request.GET.get('page')
	page_obj = paginator.get_page(page_number)

	context = {
		'q': q,
		'page_obj': page_obj,
	}
	return render(request, 'core/classes_list.html', context)


def class_create(request):
	"""Create a new SchoolClass."""
	if request.method == 'POST':
		form = SchoolClassForm(request.POST)
		if form.is_valid():
			klass = form.save()
			# handle semester value (posted as repeated/hidden field named 'semester')
			sem_val = request.POST.get('semester')
			if sem_val:
				# convert Persian digits to ascii if necessary
				def persian_to_ascii(s: str) -> str:
					mapping = {'۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9'}
					return ''.join(mapping.get(ch, ch) for ch in s)
				try:
					num = int(persian_to_ascii(sem_val.strip()))
				except Exception:
					num = None
				if num is not None:
					from .models import Semester
					sem_obj, _ = Semester.objects.get_or_create(number=num)
					klass.semester = sem_obj
					klass.save()
			messages.success(request, 'صنف با موفقیت ثبت شد.')
			return redirect(reverse('core:classes_list'))
	else:
		form = SchoolClassForm()
	# provide existing semesters from DB so frontend can show them
	from .models import Semester
	semester_qs = Semester.objects.order_by('number')
	semester_names = [{'value': str(s.number), 'label': str(s)} for s in semester_qs]
	return render(request, 'core/class_form.html', {'form': form, 'semester_names': semester_names})


def class_edit(request, pk):
	"""Edit an existing SchoolClass."""
	klass = get_object_or_404(SchoolClass, pk=pk)
	if request.method == 'POST':
		form = SchoolClassForm(request.POST, instance=klass)
		if form.is_valid():
			klass = form.save()
			sem_val = request.POST.get('semester')
			if sem_val is not None:
				def persian_to_ascii(s: str) -> str:
					mapping = {'۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4', '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9'}
					return ''.join(mapping.get(ch, ch) for ch in s)
				try:
					num = int(persian_to_ascii(sem_val.strip()))
				except Exception:
					num = None
				if num is not None:
					from .models import Semester
					sem_obj, _ = Semester.objects.get_or_create(number=num)
					klass.semester = sem_obj
				else:
					klass.semester = None
				klass.save()
			messages.success(request, 'اطلاعات صنف با موفقیت بروزرسانی شد.')
			return redirect(reverse('core:classes_list'))
	else:
		form = SchoolClassForm(instance=klass)
	from .models import Semester
	semester_qs = Semester.objects.order_by('number')
	semester_names = [{'value': str(s.number), 'label': str(s)} for s in semester_qs]
	selected_semester = str(klass.semester.number) if klass.semester else ''
	return render(request, 'core/class_form.html', {'form': form, 'semester_names': semester_names, 'selected_semester': selected_semester})


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
	student = get_object_or_404(Student, pk=pk)
	if request.method == 'POST':
		form = StudentForm(request.POST, request.FILES, instance=student)
		if form.is_valid():
			form.save()
			messages.success(request, 'اطلاعات دانش‌آموز با موفقیت بروزرسانی شد.')
			return redirect(reverse('core:student_list'))
	else:
		form = StudentForm(instance=student)
	class_names = list(SchoolClass.objects.values_list('name', flat=True))
	return render(request, 'core/student_form_clean.html', {'form': form, 'class_names': class_names})


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
	total_students = Student.objects.count()
	total_teachers = Teacher.objects.count()
	total_subjects = Subject.objects.count()
	total_classes = SchoolClass.objects.count()

	# Prepare data for pie chart: count of students per SchoolClass name.
	classes = list(SchoolClass.objects.order_by('name'))
	labels = [c.name for c in classes]
	counts = [Student.objects.filter(class_name=c.name).count() for c in classes]

	# If there are no defined classes but some students have class_name values,
	# fall back to grouping by student.class_name values.
	if not classes:
		from django.db.models import Count
		qs = Student.objects.values('class_name').annotate(cnt=Count('id')).order_by('-cnt')
		labels = [x['class_name'] or 'نامشخص' for x in qs]
		counts = [x['cnt'] for x in qs]

	chart_json = mark_safe(json.dumps({'labels': labels, 'data': counts}))

	context = {
		'total_students': total_students,
		'total_teachers': total_teachers,
		'total_subjects': total_subjects,
		'total_classes': total_classes,
		'chart_json': chart_json,
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
		sems = list(s.semesters.values_list('number', flat=True)) if hasattr(s, 'semesters') else []
		# fallback: determine semester from student's class_name -> SchoolClass.semester
		if not sems:
			try:
				if s.class_name:
					klass = SchoolClass.objects.filter(name=s.class_name).first()
					if klass and klass.semester:
						sems = [klass.semester.number]
			except Exception:
				# any unexpected issue -> keep sems empty
				sems = []
		students.append({
			'id': s.id,
			'display': f"{s.name} ({s.father_name})",
			'semesters': sems,
			'class_name': s.class_name or ''
		})

	subjects_qs = Subject.objects.order_by('name')
	# include semester number for each subject
	subjects = [{'id': sub.id, 'name': sub.name, 'semester': sub.semester} for sub in subjects_qs]

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

		# Do not redirect: render the form again and show which subjects were saved
		messages.success(request, f'عملیات ثبت نمرات انجام شد. ایجاد: {created} — بروزرسانی: {updated} — خطاها: {errors}')
		# Rebuild subjects list for template render (same as GET below)
		subjects_qs = Subject.objects.order_by('name')
		subjects = [{'id': sub.id, 'name': sub.name, 'semester': sub.semester} for sub in subjects_qs]
		return render(request, 'core/grades_form.html', {'students': students, 'subjects': subjects, 'saved_subjects': saved_subjects, 'saved_student_id': student.id})

	# GET
	return render(request, 'core/grades_form.html', {'students': students, 'subjects': subjects})
