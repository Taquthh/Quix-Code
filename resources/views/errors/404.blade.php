@include('errors.page', [
    'status' => '404',
    'title' => __('public.errors.404.title'),
    'message' => __('public.errors.404.body'),
])
