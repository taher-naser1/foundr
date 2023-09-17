<div class="text-end pe-16">
    @if(!empty($row->selling_price))
        {{ getCurrencyFormat(getCurrencyCode(),$row->selling_price) }}
    @else
    {{ __('messages.common.n/a') }}
    @endif
</div>
