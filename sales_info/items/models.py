from django.db import models
from django.core.validators import RegexValidator
from django.contrib.auth.models import User

# Create your models here.
class Item(models.Model):
    title = models.CharField(max_length=100)
    condChoices = (
        ('0', 'new'),
        ('1', 'used:perfect'),
        ('2', 'used:good'),
        ('3', 'used:poor'),
    )
    condition = models.CharField(max_length=1, choices=condChoices, default='0')
    cateChoices = (
        ('0', 'clothes'),
        ('1', 'electronics'),
        ('2', 'books'),
        ('3', 'furniture'),
        ('4', 'others'),
    )
    category = models.CharField(max_length=1, choices=cateChoices, default='4')
    description = models.TextField()
    zip_val = RegexValidator(r'^\d{5}$', 'Only five digits zip code is allowed.')
    zipcode = models.CharField(validators=[zip_val], max_length=5, blank=True)
    price = models.DecimalField(max_digits=6, decimal_places=2, default=0.00)
    phone_val = RegexValidator(r'^\+?1?\d{9,15}$', 'Valid phone number')
    contact = models.CharField(validators=[phone_val], max_length=17, blank=True)
    date = models.DateTimeField(auto_now_add=True)
    image = models.ImageField(default='default.jpg', blank=True)
    seller = models.ForeignKey(User, on_delete=models.CASCADE, default=None)

    def __str__(self):
        return self.title

class Comment(models.Model):
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    item = models.ForeignKey(Item, on_delete=models.CASCADE)
    comment = models.TextField()
    date = models.DateTimeField(auto_now_add=True)

from django.db.models.signals import post_delete, pre_save
from django.dispatch.dispatcher import receiver

# https://djangosnippets.org/snippets/10638/
@receiver(post_delete, sender=Item)
def image_auto_delete(sender, instance, **kwargs):
    # Pass false so FileField doesn't save the model.
    if instance.image.url != '/media/default.jpg':
        instance.image.delete(False)

# https://djangosnippets.org/snippets/10638/
@receiver(pre_save, sender=Item)
def auto_delete_file_on_change(sender, instance, **kwargs):
    if not instance.id:
        return False
    try:
        old_image = sender.objects.get(id=instance.id).image
    except sender.DoesNotExist:
        return False

    new_image = instance.image
    if not old_image == new_image:
       old_image.delete(False)
