from django import forms
from .models import Student, SchoolClass, Subject
from .models import Teacher


class StudentForm(forms.ModelForm):
    class Meta:
        model = Student
        fields = [
            'name',
            'father_name',
            'grandfather_name',
            'id_number',
            'gender',
            'current_address',
            'permanent_address',
            'time_start',
            'time_end',
            'exam_number',
            'class_name',
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
            'current_address': 'نشانی فعلی',
            'permanent_address': 'نشانی دایمی',
            'class_name': 'صنف',
            'mobile_number': 'شماره موبایل',
            'image': 'عکس',
        }
        widgets = {
            'current_address': forms.Textarea(attrs={'rows': 3}),
            'permanent_address': forms.Textarea(attrs={'rows': 3}),
            'gender': forms.Select(attrs={'class': 'border border-gray-300 rounded px-2 py-1'}),
            'time_start': forms.TimeInput(attrs={'type': 'time', 'class': 'border border-gray-300 rounded px-2 py-1'}),
            'time_end': forms.TimeInput(attrs={'type': 'time', 'class': 'border border-gray-300 rounded px-2 py-1'}),
            'id_number': forms.TextInput(attrs={'class': 'border border-gray-300 rounded px-2 py-1 w-full'}),
            'exam_number': forms.TextInput(attrs={'class': 'border border-gray-300 rounded px-2 py-1 w-full'}),
        }


class SchoolClassForm(forms.ModelForm):
    class Meta:
        model = SchoolClass
        fields = [
            'name',
        ]
        labels = {
            'name': 'نام صنف',
        }
        widgets = {
            'current_address': forms.Textarea(attrs={'rows': 3}),
            'permanent_address': forms.Textarea(attrs={'rows': 3}),
        }


class SubjectForm(forms.ModelForm):
    class Meta:
        model = Subject
        fields = [
            'name',
            'semester',
        ]
        labels = {
            'name': 'نام مضمون',
            'semester': 'سمستر مربوطه',
        }
        widgets = {
            'name': forms.TextInput(attrs={
                'class': 'border border-gray-300 rounded px-2 py-1 w-full',
                'placeholder': 'نام مضمون را وارد کنید',
            }),
            'semester': forms.Select(choices=Subject.SEMESTER_CHOICES, attrs={
                'class': 'border border-gray-300 rounded px-2 py-1',
            }),
        }

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
