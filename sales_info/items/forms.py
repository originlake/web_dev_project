from django import forms
from . import models

class NewPostForm(forms.ModelForm):
    class Meta:
        model = models.Item
        fields = ['title','condition','category','description','price','zipcode','contact','image']

class NewCommentForm(forms.ModelForm):
    class Meta:
        model = models.Comment
        fields = ['comment']