from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('core', '0022_add_birth_date'),
    ]

    operations = [
        migrations.AddField(
            model_name='teacher',
            name='plan_file',
            field=models.FileField('پلان درسی', upload_to='teacher_plans/', blank=True, null=True),
        ),
    ]
