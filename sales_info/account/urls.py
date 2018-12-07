from django.urls import path, include, re_path
from . import views

app_name = 'account'

urlpatterns = [
    re_path(r'^signup/$', views.signup_view, name='signup'),
    re_path(r'^login/$', views.login_view, name='login'),
    re_path(r'^logout/$', views.logout_view, name='logout'),
    re_path(r'^reset/$', views.reset_view, name='reset'),
    re_path(r'^delete/$', views.delete_view, name='delete'),
    re_path(r'^manage/$', views.manage_view, name='manage'),
]
