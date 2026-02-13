from django import forms
import datetime
from django.core.exceptions import ValidationError
from .models import Student, SchoolClass, Subject, StudyLevel, CoursePeriod
from .models import Teacher, TeacherContract


def _persian_to_ascii(value: str) -> str:
    mapping = {
        '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
        '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
    }
    return ''.join(mapping.get(ch, ch) for ch in str(value))


def _jalali_to_gregorian(jy: int, jm: int, jd: int):
    jy = int(jy) + 1595
    days = -355668 + (365 * jy) + (jy // 33) * 8 + ((jy % 33) + 3) // 4 + jd
    if jm < 7:
        days += (jm - 1) * 31
    else:
        days += ((jm - 7) * 30) + 186
    gy = 400 * (days // 146097)
    days %= 146097
    if days > 36524:
        gy += 100 * ((days - 1) // 36524)
        days = (days - 1) % 36524
        if days >= 365:
            days += 1
    gy += 4 * (days // 1461)
    days %= 1461
    if days > 365:
        gy += (days - 1) // 365
        days = (days - 1) % 365
    gd = days + 1
    sal_a = [0, 31, 29 if ((gy % 4 == 0 and gy % 100 != 0) or (gy % 400 == 0)) else 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]
    gm = 1
    while gm <= 12 and gd > sal_a[gm]:
        gd -= sal_a[gm]
        gm += 1
    return gy, gm, gd


def _gregorian_to_jalali(gy: int, gm: int, gd: int):
    g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334]
    gy2 = gy + 1 if gm > 2 else gy
    days = (
        355666
        + (365 * gy)
        + ((gy2 + 3) // 4)
        - ((gy2 + 99) // 100)
        + ((gy2 + 399) // 400)
        + gd
        + g_d_m[gm - 1]
    )
    jy = -1595 + 33 * (days // 12053)
    days %= 12053
    jy += 4 * (days // 1461)
    days %= 1461
    if days > 365:
        jy += (days - 1) // 365
        days = (days - 1) % 365
    jm = 1 + (days // 31) if days < 186 else 7 + ((days - 186) // 30)
    jd = 1 + (days % 31) if days < 186 else 1 + ((days - 186) % 30)
    return jy, jm, jd


def _parse_birth_date(value: str):
    raw = _persian_to_ascii(value).strip()
    if not raw:
        return None
    raw = raw.replace('-', '/').replace('.', '/')
    parts = [p for p in raw.split('/') if p]
    if len(parts) != 3:
        raise ValueError('invalid date')
    year, month, day = map(int, parts)
    if year >= 1700:
        return datetime.date(year, month, day)
    gy, gm, gd = _jalali_to_gregorian(year, month, day)
    return datetime.date(gy, gm, gd)


class StudentForm(forms.ModelForm):
    birth_date = forms.CharField(
        label='تاریخ تولد',
        required=True,
        widget=forms.TextInput(attrs={
            'class': 'border border-gray-300 rounded px-2 py-1 w-full',
            'placeholder': 'مثلاً ۱۴۰۲/۰۳/۱۵',
            'data-jdp': '',
            'autocomplete': 'off',
        }),
    )

    class Meta:
        model = Student
        fields = [
            'name',
            'father_name',
            'grandfather_name',
            'birth_date',
            'id_number',
            'gender',
            'level',
            'is_grade12_graduate',
            'semesters',
            'periods',
            'village',
            'district',
            'area',
            'current_address',
            'permanent_address',
            'time_start',
            'time_end',
            'exam_number',
            'school_class',
            'mobile_number',
            'image',
        ]
        labels = {
            'name': 'نام دانش‌آموز',
            'father_name': 'نام پدر',
            'grandfather_name': 'نام پدر کلان',
            'birth_date': 'تاریخ تولد',
            'id_number': 'نمبر تذکره',
            'gender': 'جنسیت',
            'exam_number': 'نمبر امتحان کانکور',
            'level': 'سطح آموزشی',
            'is_grade12_graduate': 'فارغ صنف دوازدهم',
            'semesters': 'سمسترها',
            'periods': 'دوره‌ها',
            'village': 'قریه',
            'district': 'ولسوالی',
            'area': 'ناحیه',
            'current_address': 'نشانی فعلی',
            'permanent_address': 'نشانی دایمی',
            'school_class': 'صنف',
            'mobile_number': 'شماره موبایل',
            'image': 'عکس',
        }
        widgets = {
            'current_address': forms.Textarea(attrs={'rows': 3}),
            'permanent_address': forms.Textarea(attrs={'rows': 3}),
            'gender': forms.Select(attrs={'class': 'border border-gray-300 rounded px-2 py-1'}),
            'level': forms.Select(attrs={'class': 'border border-gray-300 rounded px-2 py-1 w-full'}),
            'is_grade12_graduate': forms.CheckboxInput(attrs={'class': 'h-4 w-4 text-blue-600 border-gray-300 rounded'}),
            'semesters': forms.CheckboxSelectMultiple(),
            'periods': forms.CheckboxSelectMultiple(),
            'time_start': forms.TimeInput(attrs={'type': 'time', 'class': 'border border-gray-300 rounded px-2 py-1'}),
            'time_end': forms.TimeInput(attrs={'type': 'time', 'class': 'border border-gray-300 rounded px-2 py-1'}),
            'id_number': forms.TextInput(attrs={'class': 'border border-gray-300 rounded px-2 py-1 w-full'}),
            'exam_number': forms.TextInput(attrs={'class': 'border border-gray-300 rounded px-2 py-1 w-full'}),
            'school_class': forms.Select(attrs={'class': 'border border-gray-300 rounded px-2 py-1'}),
        }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        if 'level' in self.fields:
            self.fields['level'].required = True
        if 'semesters' in self.fields:
            self.fields['semesters'].required = False
        if 'periods' in self.fields:
            self.fields['periods'].required = False
        if self.instance and self.instance.birth_date:
            jy, jm, jd = _gregorian_to_jalali(self.instance.birth_date.year, self.instance.birth_date.month, self.instance.birth_date.day)
            self.initial['birth_date'] = f"{jy:04d}/{jm:02d}/{jd:02d}"

    def clean_birth_date(self):
        raw = self.cleaned_data.get('birth_date')
        if not raw:
            raise ValidationError('تاریخ تولد الزامی است.')
        try:
            return _parse_birth_date(raw)
        except Exception:
            raise ValidationError('تاریخ تولد نامعتبر است.')

    def clean(self):
        cleaned = super().clean()
        level = cleaned.get('level')
        is_grad = cleaned.get('is_grade12_graduate')
        semesters = cleaned.get('semesters')
        periods = cleaned.get('periods')
        school_class = cleaned.get('school_class')

        if not level:
            self.add_error('level', 'لطفاً سطح آموزشی را انتخاب کنید.')
            return cleaned

        level_code = getattr(level, 'code', '')
        if level_code == 'aali':
            if not is_grad:
                self.add_error('is_grade12_graduate', 'برای دوره عالی، فارغ بودن از صنف دوازدهم الزامی است.')
            if not semesters:
                self.add_error('semesters', 'لطفاً حداقل یک سمستر را انتخاب کنید.')
            cleaned['periods'] = []
        else:
            if is_grad:
                self.add_error('is_grade12_graduate', 'برای دوره ابتداییه/متوسطه، فارغ صنف دوازدهم نباید باشد.')
            if not periods:
                self.add_error('periods', 'لطفاً حداقل یک دوره را انتخاب کنید.')
            cleaned['semesters'] = []

        if level and school_class and school_class.level and school_class.level_id != level.id:
            self.add_error('level', 'سطح انتخاب‌شده با سطح صنف انتخاب‌شده مطابقت ندارد.')

        return cleaned


class SchoolClassForm(forms.ModelForm):
    class Meta:
        model = SchoolClass
        fields = [
            'name',
            'level',
        ]
        labels = {
            'name': 'نام صنف',
            'level': 'سطح آموزشی',
        }
        widgets = {
            'current_address': forms.Textarea(attrs={'rows': 3}),
            'permanent_address': forms.Textarea(attrs={'rows': 3}),
            'level': forms.Select(attrs={'class': 'border border-gray-300 rounded px-2 py-1 w-full'}),
        }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        if 'level' in self.fields:
            self.fields['level'].required = True


class SubjectForm(forms.ModelForm):
    class Meta:
        model = Subject
        fields = [
            'name',
            'level',
            'semester',
            'period',
        ]
        labels = {
            'name': 'نام مضمون',
            'level': 'سطح آموزشی',
            'semester': 'سمستر مربوطه',
            'period': 'دوره',
        }
        widgets = {
            'name': forms.TextInput(attrs={
                'class': 'border border-gray-300 rounded px-2 py-1 w-full',
                'placeholder': 'نام مضمون را وارد کنید',
            }),
            'level': forms.Select(attrs={
                'class': 'border border-gray-300 rounded px-2 py-1 w-full',
            }),
            'semester': forms.Select(choices=Subject.SEMESTER_CHOICES, attrs={
                'class': 'border border-gray-300 rounded px-2 py-1',
            }),
            'period': forms.Select(attrs={
                'class': 'border border-gray-300 rounded px-2 py-1 w-full',
            }),
        }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        if 'level' in self.fields:
            self.fields['level'].required = True
            self.fields['level'].queryset = StudyLevel.objects.all()
        if 'period' in self.fields:
            self.fields['period'].required = False
            self.fields['period'].queryset = CoursePeriod.objects.order_by('number')
        if 'semester' in self.fields:
            self.fields['semester'].required = False

    def clean(self):
        cleaned = super().clean()
        level = cleaned.get('level')
        semester = cleaned.get('semester')
        period = cleaned.get('period')

        if not level:
            self.add_error('level', 'لطفاً سطح آموزشی را انتخاب کنید.')
            return cleaned

        if level.code == 'aali':
            if not semester:
                self.add_error('semester', 'لطفاً سمستر را انتخاب کنید.')
            cleaned['period'] = None
        else:
            if not period:
                self.add_error('period', 'لطفاً دوره را انتخاب کنید.')
            # ensure semester has a valid default for non-aali
            if not semester:
                cleaned['semester'] = 1

        return cleaned

class TeacherForm(forms.ModelForm):
    birth_date = forms.CharField(
        label='تاریخ تولد',
        required=True,
        widget=forms.TextInput(attrs={
            'class': 'border border-gray-300 rounded px-2 py-1 w-full',
            'placeholder': 'مثلاً ۱۴۰۲/۰۳/۱۵',
            'data-jdp': '',
            'autocomplete': 'off',
        }),
    )

    class Meta:
        model = Teacher
        # classes and subjects are handled in the template via JS and sent as
        # repeated `classes` and `subjects` fields; we only include primitive
        # fields here.
        fields = [
            'name',
            'father_name',
            'birth_date',
            'village',
            'district',
            'area',
            'permanent_address',
            'current_address',
            'gender',
            'education_level',
            'id_number',
            'image',
        ]
        labels = {
            'name': 'نام و تخلص استاد',
            'father_name': 'نام پدر',
            'birth_date': 'تاریخ تولد',
            'village': 'قریه',
            'district': 'ولسوالی',
            'area': 'ناحیه',
            'permanent_address': 'سکونت اصلی',
            'current_address': 'سکونت فعلی',
            'gender': 'جنسیت',
            'education_level': 'سویه تحصیلی',
            'id_number': 'نمبر تذکره',
        }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        if self.instance and self.instance.birth_date:
            jy, jm, jd = _gregorian_to_jalali(self.instance.birth_date.year, self.instance.birth_date.month, self.instance.birth_date.day)
            self.initial['birth_date'] = f"{jy:04d}/{jm:02d}/{jd:02d}"

    def clean_birth_date(self):
        raw = self.cleaned_data.get('birth_date')
        if not raw:
            raise ValidationError('تاریخ تولد الزامی است.')
        try:
            return _parse_birth_date(raw)
        except Exception:
            raise ValidationError('تاریخ تولد نامعتبر است.')


class TeacherContractForm(forms.ModelForm):
    class Meta:
        model = TeacherContract
        fields = [
            'contract_date',
            'monthly_salary',
            'position',
            'signed_file',
        ]
        labels = {
            'contract_date': 'تاریخ قرارداد',
            'monthly_salary': 'معاش ماهوار',
            'position': 'وظیفه/سمت',
            'signed_file': 'فایل قرارداد امضاشده',
        }
        widgets = {
            'contract_date': forms.TextInput(attrs={
                'class': 'border border-gray-300 rounded px-2 py-1 w-full',
                'placeholder': 'مثلاً ۱۴۰۲/۰۵/۱۰',
                'data-jdp': '',
                'autocomplete': 'off',
            }),
            'monthly_salary': forms.TextInput(attrs={'class': 'border border-gray-300 rounded px-2 py-1 w-full', 'placeholder': 'مثلاً ۱۵۰۰۰ افغانی'}),
            'position': forms.TextInput(attrs={'class': 'border border-gray-300 rounded px-2 py-1 w-full'}),
            'signed_file': forms.ClearableFileInput(attrs={
                'class': 'border border-gray-300 rounded px-2 py-1 w-full text-sm',
                'accept': '.pdf,image/*',
            }),
        }

class StudentScoreForm(forms.ModelForm):
    class Meta:
        from .models import StudentScore
        model = StudentScore
        fields = ['subject', 'score']
        labels = {
            'subject': 'مضمون',
            'score': 'نمره',
        }
        widgets = {
            'subject': forms.Select(attrs={'class': 'border border-gray-300 rounded px-2 py-1'}),
            'score': forms.NumberInput(attrs={'class': 'border border-gray-300 rounded px-2 py-1 w-24', 'min': 0, 'max': 100}),
        }
        widgets = {
            'permanent_address': forms.Textarea(attrs={'rows': 3}),
            'current_address': forms.Textarea(attrs={'rows': 3}),
            'gender': forms.Select(attrs={'class': 'border border-gray-300 rounded px-2 py-1'}),
            'education_level': forms.Select(attrs={'class': 'border border-gray-300 rounded px-2 py-1'}),
            'name': forms.TextInput(attrs={
                'class': 'border border-gray-300 rounded px-2 py-1 w-full',
            }),
            'id_number': forms.TextInput(attrs={
                'class': 'border border-gray-300 rounded px-2 py-1 w-full',
            }),
        }
