from django.shortcuts import render, redirect
from django.contrib.auth.forms import UserCreationForm, AuthenticationForm, PasswordChangeForm
from django.contrib.auth import authenticate,login,logout,update_session_auth_hash
from django.contrib.auth.decorators import login_required
from django.contrib import messages
from .forms import DeleteUserForm
from django.contrib.auth.models import User
from items.models import Item

# Based on The Net Ninja's tutorial https://www.youtube.com/watch?v=n-FTlQ7Djqc&list=PL4cUxeGkcC9ib4HsrXEYpQnTOTZE1x0uc&index=1
# Create your views here.
def signup_view(request):
    if request.user.is_authenticated:
        return redirect('home')
    if request.method == 'POST':
        form = UserCreationForm(request.POST)
        if form.is_valid():
            user = form.save()
            login(request, user)
            messages.success(request, 'sign up success')
            if 'next' in request.POST:
                return redirect(request.POST.get('next'))
            return redirect('home')
    else:
        form = UserCreationForm()
    return render(request, 'account/signup.html', {'form': form})

def login_view(request):
    if request.user.is_authenticated:
        return redirect('home')

    if request.method == 'POST':
        form = AuthenticationForm(data=request.POST)
        if form.is_valid():
            user = form.get_user()
            login(request, user)
            messages.success(request, 'log in success')
            if 'next' in request.POST:
                return redirect(request.POST.get('next'))
            return redirect('home')

    else:
        form = AuthenticationForm()
    return render(request, 'account/login.html', {'form': form})

@login_required(login_url='/account/login/')
def logout_view(request):
    logout(request)
    return redirect('home')

@login_required(login_url='/account/login/')
def reset_view(request):
    if request.method == "POST":
        form = PasswordChangeForm(request.user, request.POST)
        if form.is_valid():
            user = form.save()
            update_session_auth_hash(request, user)
            messages.success(request, 'reset password success')
            if 'next' in request.POST:
                return redirect(request.POST.get('next'))
            return redirect('account:manage')
    else:
        form = PasswordChangeForm(request.user)
    return render(request, 'account/reset.html', {'form': form})

@login_required(login_url='/account/login/')
def delete_view(request):
    if request.method == 'POST':
        form = DeleteUserForm(request.user, request.POST)
        if form.is_valid():
            username = request.user.username
            logout(request)
            User.objects.filter(username=username).delete()
            if 'next' in request.POST:
                return redirect(request.POST.get('next'))
            return redirect('home')
    else:
        form = DeleteUserForm(request.user)
    return render(request, 'account/delete.html', {'form': form})

@login_required(login_url='/account/login/')
def manage_view(request):
    items = Item.objects.filter(seller=request.user).order_by('date')
    return render(request, 'account/manage.html', {'items': items})
