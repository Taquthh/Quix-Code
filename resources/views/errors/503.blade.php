@include('errors.page', [
    'status' => '503',
    'title' => __('public.errors.503.title'),
    'message' => __('public.errors.503.body'),
])
