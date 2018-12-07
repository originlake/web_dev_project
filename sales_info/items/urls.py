from django.urls import path, include, re_path
from . import views

app_name = 'items'

urlpatterns = [
    path('post/', views.post_view, name='post'),
    path('item/<int:id>/', views.item_view, name='item'),
    path('edit/<int:id>/', views.edit_view, name='edit'),
    path('delete/<int:id>/', views.delete_view, name='delete'),
    path('list/', views.list_view, name='list'),
]
