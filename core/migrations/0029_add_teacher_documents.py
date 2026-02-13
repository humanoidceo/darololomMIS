from django.db import migrations, models


class Migration(migrations.Migration):

	dependencies = [
		('core', '0028_add_student_certificate_number'),
	]

	operations = [
		migrations.AddField(
			model_name='teacher',
			name='education_document',
			field=models.FileField(blank=True, null=True, upload_to='teacher_documents/education/', verbose_name='اسناد تحصیلی'),
		),
		migrations.AddField(
			model_name='teacher',
			name='experience_document',
			field=models.FileField(blank=True, null=True, upload_to='teacher_documents/experience/', verbose_name='اسناد تجربه کاری'),
		),
	]
