from django.shortcuts import render, redirect, get_object_or_404
from .forms import NewPostForm, NewCommentForm
from .models import Item, Comment
from django.http import HttpResponseForbidden
from django.contrib.auth.decorators import login_required

# Create your views here.
@login_required(login_url='/account/login/')
def post_view(request):
    if request.method == 'POST':
        form = NewPostForm(request.POST, request.FILES)
        if form.is_valid():
            instance = form.save(commit=False)
            instance.seller = request.user
            instance.save()
            return redirect('items:item', id=instance.id)
    else:
        form = NewPostForm()
    return render(request, 'items/post.html', {'form': form})

def item_view(request, id):
    item = get_object_or_404(Item, id=id)
    if request.method == 'POST':
        if not request.user.is_authenticated:
            # https://stackoverflow.com/questions/3765887/add-request-get-variable-using-django-shortcuts-redirect
            res = redirect('account:login')
            res['Location'] += '?next={}'.format(request.path)
            return res
        form = NewCommentForm(request.POST)
        if form.is_valid():
            instance = form.save(commit=False)
            instance.user = request.user
            instance.item = item
            instance.save()
    form = NewCommentForm()
    comments = Comment.objects.filter(item=item).order_by('date')
    return render(request, 'items/item.html', {'item': item, 'comments': comments, 'form': form})

# https://stackoverflow.com/a/1854453
@login_required(login_url='/account/login/')
def edit_view(request, id):
    item = get_object_or_404(Item, id=id)
    if item.seller != request.user:
        return HttpResponseForbidden()
    if request.method == 'POST':
        form = NewPostForm(request.POST, request.FILES, instance=item)
        if form.is_valid():
            form.save()
            return redirect('items:item', id=id)
    else:
        form = NewPostForm(None, instance=item)
    return render(request, 'items/edit.html', {'form': form, 'id': id})

@login_required(login_url='/account/login/')
def delete_view(request, id):
    item = get_object_or_404(Item, id=id)
    if item.seller != request.user:
        return HttpResponseForbidden()
    item.delete()
    return redirect('account:manage')

def list_view(request):
    # simple search method, weighted by occurrence of words (Can be slow because it extracts data from database instead of using Queryset)
    def search_by_occurence(search):
        occur = dict()
        word_list = search.split()
        if len(word_list)==0:
            return Item.objects.all()
        for word in word_list:
            qSet = Item.objects.filter(title__icontains=word)
            for item in qSet:
                occur[item.id] = occur.get(item.id, 0)+1
        res = sorted(occur.items(), key=lambda x: x[1], reverse=True)
        ids = [x[0] for x in res]
        return Item.objects.filter(id__in=ids)

    if request.GET.get('search'):
        items = search_by_occurence(request.GET.get('search'))
    else:
        items = Item.objects.all()

    if request.GET.get('condition'):
        cond = request.GET.get('condition')
        if cond in ['0','1','2','3']:
            items = items.filter(condition=cond)
    if request.GET.get('category'):
        cate = request.GET.get('category')
        if cate in ['0','1','2','3','4']:
            items = items.filter(category=cate)
    if request.GET.get('min_price') and request.GET.get('max_price'):
        min_price, max_price = request.GET.get('min_price'), request.GET.get('max_price')
        if float(min_price) <= float(max_price) and float(min_price) != 0:
            items = items.filter(price__range=(float(min_price), float(max_price)))
    if request.GET.get('zipcode'):
        zipcode = request.GET.get('zipcode')
        items = items.filter(zipcode=zipcode)
    if request.GET.get('order'):
        order = request.GET.get('order')
        if order == 'date_asc':
            items = items.order_by('date')
        elif order == 'price_asc':
            items = items.order_by('price')
        elif order == 'price_dsc':
            items = items.order_by('-price')
        else:
            items = items.order_by('date')
    else:
        items = items.order_by('date')

    return render(request, 'items/list.html', {'items': items})
