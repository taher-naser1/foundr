<div class="d-flex justify-content-end pe-16">
    @if(!empty($row->buying_price))
        {{ getCurrencyFormat(getCurrencyCode(),$row->buying_price) }}
    @else
        {{ __('messages.common.n/a') }}
    @endif
</div>
