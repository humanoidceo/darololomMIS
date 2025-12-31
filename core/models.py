from django.db import models


class Student(models.Model):
	name = models.CharField('نام', max_length=255)
	father_name = models.CharField('نام پدر', max_length=255, blank=True)
	# نام پدر کلان
	grandfather_name = models.CharField('نام پدر کلان', max_length=255, blank=True)

	# شماره تذکره
	id_number = models.CharField('نمبر تذکره', max_length=100, blank=True)

	# شماره امتحان کانکور
	exam_number = models.CharField('نمبر امتحان کانکور', max_length=100, blank=True)

	# جنسیت: فقط دو آپشن (مذکر / مونث)
	GENDER_CHOICES = [
		('male', 'مذکر'),
		('female', 'مونث'),
	]
	gender = models.CharField('جنسیت', max_length=10, choices=GENDER_CHOICES, default='male')
	current_address = models.TextField('نشانی فعلی', blank=True)

	# یک جفت تایم مشترک (تایم آغاز / تایم ختم)
	time_start = models.TimeField('تایم آغاز', blank=True, null=True)
	time_end = models.TimeField('تایم ختم', blank=True, null=True)

	permanent_address = models.TextField('نشانی دایمی', blank=True)
	# Keep as a simple text field for now. Allow blank and provide a default
	# so adding new models (like SchoolClass) doesn't force an interactive
	# migration prompt for existing rows.
	class_name = models.CharField(max_length=100, blank=True, default='')
	mobile_number = models.CharField('شماره موبایل', max_length=20, blank=True)
	image = models.ImageField('عکس', upload_to='students/', blank=True, null=True)
	created_at = models.DateTimeField('ایجاد شده در', auto_now_add=True)

	# Allow assigning one or more semesters to a student (useful for filtering subjects)
	semesters = models.ManyToManyField('Semester', verbose_name='سمسترها', blank=True)

	class Meta:
		verbose_name = 'دانش‌آموز'
		verbose_name_plural = 'دانش‌آموزان'

	def __str__(self) -> str:
		return f"{self.name} ({self.father_name})"


class SchoolClass(models.Model):
	"""Model representing a school class (صنف)."""
	name = models.CharField('نام صنف', max_length=255, unique=True)
	# Optional link to a Semester object. Kept nullable to avoid
	# forcing immediate backfills when adding the field.
	semester = models.ForeignKey('Semester', verbose_name='سمستر', null=True, blank=True, on_delete=models.SET_NULL)
	created_at = models.DateTimeField('ایجاد شده در', auto_now_add=True)

	class Meta:
		verbose_name = 'صنف'
		verbose_name_plural = 'صنوف'

	def __str__(self) -> str:
		return self.name


class Subject(models.Model):
	"""Model representing an academic subject (مضمون)."""
	SEMESTER_CHOICES = [
		(1, '1'),
		(2, '2'),
		(3, '3'),
		(4, '4'),
	]
	name = models.CharField('نام مضمون', max_length=255, unique=True)
	semester = models.PositiveSmallIntegerField('سمستر مربوطه', choices=SEMESTER_CHOICES, default=1)
	created_at = models.DateTimeField('ایجاد شده در', auto_now_add=True)

	class Meta:
		verbose_name = 'مضمون'
		verbose_name_plural = 'مضامین'

	def __str__(self) -> str:
		return f"{self.name} (سمستر {self.semester})"


class Teacher(models.Model):
	"""Model representing a teacher (استاد)."""
	GENDER_CHOICES = [
		('male', 'مرد'),
		('female', 'زن'),
	]

	EDUCATION_CHOICES = [
		('p', 'چهارده پاس'),
		('b', 'لیسانس'),
		('m', 'ماستر'),
		('d', 'دوکتور'),
	]

	SEMESTER_CHOICES = [
		(1, '1'),
		(2, '2'),
		(3, '3'),
		(4, '4'),
	]

	name = models.CharField('نام و تخلص استاد', max_length=255)
	father_name = models.CharField('نام پدر', max_length=255, blank=True)
	permanent_address = models.TextField('سکونت اصلی', blank=True)
	current_address = models.TextField('سکونت فعلی', blank=True)
	gender = models.CharField('جنسیت', max_length=10, choices=GENDER_CHOICES, default='male')
	education_level = models.CharField('سویه تحصیلی', max_length=2, choices=EDUCATION_CHOICES, default='p')
	id_number = models.CharField('نمبر تذکره', max_length=100, blank=True)

	# Relations
	classes = models.ManyToManyField(SchoolClass, verbose_name='صنوف', blank=True)
	subjects = models.ManyToManyField(Subject, verbose_name='مضامین', blank=True)
	# A teacher can be associated with one or more semesters. Use a small
	# `Semester` model so we can store multiple semester numbers.
	# Semesters (1..4) will be created on demand when assigning.
	image = models.ImageField('عکس', upload_to='teachers/', blank=True, null=True)

	# Replace single semester with M2M to Semester
	semesters = models.ManyToManyField('Semester', verbose_name='سمسترها', blank=True)

	created_at = models.DateTimeField('ایجاد شده در', auto_now_add=True)

	class Meta:
		verbose_name = 'استاد'
		verbose_name_plural = 'اساتید'

	def __str__(self) -> str:
		return f"{self.name} ({self.id_number or '—'})"


class Semester(models.Model):
	"""Small model representing a semester number (1..4)."""
	number = models.PositiveSmallIntegerField('شماره سمستر', unique=True)

	class Meta:
		verbose_name = 'سمستر'
		verbose_name_plural = 'سمسترها'

	def __str__(self) -> str:
		# return Persian numeral for display
		persian_digits = {
			'0': '۰',
			'1': '۱',
			'2': '۲',
			'3': '۳',
			'4': '۴',
			'5': '۵',
			'6': '۶',
			'7': '۷',
			'8': '۸',
			'9': '۹',
		}
		return ''.join(persian_digits.get(ch, ch) for ch in str(self.number))


class StudentScore(models.Model):
	"""Model to store a student's score for a subject."""
	student = models.ForeignKey(Student, on_delete=models.CASCADE, related_name='scores')
	subject = models.ForeignKey(Subject, on_delete=models.CASCADE)
	# Allow whole-number scores from 0..100; use PositiveSmallIntegerField
	score = models.PositiveSmallIntegerField('نمره', null=True, blank=True)
	created_at = models.DateTimeField('ایجاد شده در', auto_now_add=True)
	updated_at = models.DateTimeField('بروزرسانی شده در', auto_now=True)

	class Meta:
		verbose_name = 'نمره دانش‌آموز'
		verbose_name_plural = 'نمرات دانش‌آموزان'
		unique_together = ('student', 'subject')

	def __str__(self) -> str:
		return f"{self.student} — {self.subject}: {self.score if self.score is not None else '—'}"

def _to_persian(num: int) -> str:
	"""Helper to convert an integer 1..9 to Persian numeral string."""
	map_ = {'0': '۰', '1': '۱', '2': '۲', '3': '۳', '4': '۴', '5': '۵', '6': '۶', '7': '۷', '8': '۸', '9': '۹'}
	return ''.join(map_.get(ch, ch) for ch in str(num))


def teacher_get_persian_semesters(self) -> str:
	"""Return teacher's semesters as space-separated Persian numerals."""
	nums = [s.number for s in self.semesters.all().order_by('number')]
	return ' '.join(_to_persian(n) for n in nums)


# attach helper as method for convenience in templates
Teacher.get_persian_semesters = teacher_get_persian_semesters
