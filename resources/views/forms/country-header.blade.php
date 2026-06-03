<div class="flex items-center justify-between bg-gray-100 px-3 py-2 rounded-t-lg border-b">

    <div class="font-bold">
        {{ $countryName }}
    </div>

    <button
        type="button"
        class="px-3 py-1 text-xs bg-primary-600 text-white rounded hover:bg-primary-700"
        x-on:click="
            $wire.set('{{ $fieldName }}', {{ json_encode($options) }})
        "
    >
        Pilih Semua
    </button>

</div>
