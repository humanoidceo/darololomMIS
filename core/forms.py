from django import forms
from .models import Student, SchoolClass, Subject, StudyLevel, CoursePeriod
from .models import Teacher, TeacherContract


class StudentForm(forms.ModelForm):
    class Meta:
        model = Student
        fields = [
            'name',
            'father_name',
            'grandfather_name',
            'id_number',
            'gender',
            'level',
            'is_grade12_graduate',
            'semesters',
            'periods',
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
            'id_number': 'نمبر تذکره',
            'gender': 'جنسیت',
            'exam_number': 'نمبر امتحان کانکور',
            'level': 'سطح آموزشی',
            'is_grade12_graduate': 'فارغ صنف دوازدهم',
            'semesters': 'سمسترها',
            'periods': 'دوره‌ها',
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
    class Meta:
        model = Teacher
        # classes and subjects are handled in the template via JS and sent as
        # repeated `classes` and `subjects` fields; we only include primitive
        # fields here.
        fields = [
            'name',
            'father_name',
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
            'permanent_address': 'سکونت اصلی',
            'current_address': 'سکونت فعلی',
            'gender': 'جنسیت',
            'education_level': 'سویه تحصیلی',
            'id_number': 'نمبر تذکره',
        }


class TeacherContractForm(forms.ModelForm):
    class Meta:
        model = TeacherContract
        fields = [
            'contract_number',
            'contract_date',
            'start_date',
            'end_date',
            'monthly_salary',
            'position',
            'work_hours',
            'terms',
            'signed_file',
        ]
        labels = {
            'contract_number': 'شماره قرارداد',
            'contract_date': 'تاریخ قرارداد',
            'start_date': 'تاریخ شروع',
            'end_date': 'تاریخ ختم',
            'monthly_salary': 'معاش ماهوار',
            'position': 'وظیفه/سمت',
            'work_hours': 'ساعات کاری',
            'terms': 'شرایط و توضیحات',
            'signed_file': 'فایل قرارداد امضاشده',
        }
        widgets = {
            'contract_number': forms.TextInput(attrs={'class': 'border border-gray-300 rounded px-2 py-1 w-full'}),
            'contract_date': forms.TextInput(attrs={
                'class': 'border border-gray-300 rounded px-2 py-1 w-full',
                'placeholder': 'مثلاً ۱۴۰۲/۰۵/۱۰',
                'data-jdp': '',
                'autocomplete': 'off',
            }),
            'start_date': forms.TextInput(attrs={
                'class': 'border border-gray-300 rounded px-2 py-1 w-full',
                'placeholder': 'مثلاً ۱۴۰۲/۰۵/۱۰',
                'data-jdp': '',
                'autocomplete': 'off',
            }),
            'end_date': forms.TextInput(attrs={
                'class': 'border border-gray-300 rounded px-2 py-1 w-full',
                'placeholder': 'مثلاً ۱۴۰۲/۰۵/۱۰',
                'data-jdp': '',
                'autocomplete': 'off',
            }),
            'monthly_salary': forms.TextInput(attrs={'class': 'border border-gray-300 rounded px-2 py-1 w-full', 'placeholder': 'مثلاً ۱۵۰۰۰ افغانی'}),
            'position': forms.TextInput(attrs={'class': 'border border-gray-300 rounded px-2 py-1 w-full'}),
            'work_hours': forms.TextInput(attrs={'class': 'border border-gray-300 rounded px-2 py-1 w-full', 'placeholder': 'مثلاً ۸ صبح تا ۲ بعد از ظهر'}),
            'terms': forms.Textarea(attrs={'rows': 5, 'class': 'border border-gray-300 rounded px-2 py-1 w-full'}),
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
