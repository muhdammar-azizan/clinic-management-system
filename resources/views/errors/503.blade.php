@include('errors.layout', [
    'code' => 503,
    'title' => 'Back soon',
    'message' => 'The system is being updated. Please check again in a few minutes.',
    'hideBack' => true,
])
