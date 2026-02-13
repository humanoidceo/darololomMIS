from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ('core', '0021_remove_teacher_contract_fields'),
    ]

    operations = [
        migrations.AddField(
            model_name='student',
            name='birth_date',
            field=models.DateField('تاریخ تولد', blank=True, null=True),
        ),
        migrations.AddField(
            model_name='teacher',
            name='birth_date',
            field=models.DateField('تاریخ تولد', blank=True, null=True),
        ),
    ]
