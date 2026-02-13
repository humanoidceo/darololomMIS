from django import template

register = template.Library()


@register.filter(name='add_class')
def add_class(field, css):
    """Add a CSS class (or classes) to a form bound field's widget.

    Usage in template: {{ form.field|add_class:'class-1 class-2' }}
    """
    try:
        attrs = field.field.widget.attrs.copy() if hasattr(field, 'field') else {}
        existing = attrs.get('class', '')
        if existing:
            attrs['class'] = (existing + ' ' + css).strip()
        else:
            attrs['class'] = css
        return field.as_widget(attrs=attrs)
    except Exception:
        return field


@register.filter(name='attr')
def add_attr(field, arg):
    """Add or override an attribute on a form bound field's widget.

    Usage in template: {{ form.field|attr:'data-foo=bar' }}
    """
    try:
        attrs = field.field.widget.attrs.copy() if hasattr(field, 'field') else {}
        if '=' in arg:
            key, value = arg.split('=', 1)
            attrs[key] = value
        else:
            attrs[arg] = arg
        return field.as_widget(attrs=attrs)
    except Exception:
        return field
