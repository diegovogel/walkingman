@props(['centered' => false, 'size' => 'medium', 'buttonStyle' => 'button'])

<button @class([
    'bg-indigo-600 rounded-sm text-white uppercase hover:bg-black block' => $buttonStyle === 'button',
    'underline' => $buttonStyle === 'link',
    'px-4 py-1.5' => $size === 'medium',
    'px-2.5 py-1 text-sm' => $size === 'small',
    'px-6 py-3 text-xl' => $size === 'large',
    'mx-auto' => $centered,
])
    {{$attributes}}
>{{$slot}}</button>
