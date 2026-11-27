@include('errors.page', [
    'status' => '500',
    'title' => __('public.errors.500.title'),
    'message' => __('public.errors.500.body'),
])
