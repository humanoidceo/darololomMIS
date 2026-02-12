from django.db import migrations


class Migration(migrations.Migration):

    dependencies = [
        ('core', '0020_merge_0019_location_and_contract_number'),
    ]

    operations = [
        migrations.RemoveField(
            model_name='teachercontract',
            name='start_date',
        ),
        migrations.RemoveField(
            model_name='teachercontract',
            name='end_date',
        ),
        migrations.RemoveField(
            model_name='teachercontract',
            name='work_hours',
        ),
        migrations.RemoveField(
            model_name='teachercontract',
            name='terms',
        ),
    ]
