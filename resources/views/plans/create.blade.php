@extends('layouts.app')
@push('links')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush
@section('content')
    <x-layout>
        <form id="storePlanForm" action="{{ route('plan.store') }}" method="POST">
            @csrf
            <div class="plan-header">
                <div class="title-wrapper static md:sticky top-6 main-text">
                    Create Plan
                </div>
                <div class="plan-fields-wrapper">
                    <div class="flex flex-col lg:flex-row gap-6">
                        <div class="flex flex-col flex-1">
                            <label for="" class="ps-5">Address</label>
                            <input type="text"
                            name="address" class="inputfield py-3 px-5 placeholder:italic shadow-md shadow-gray-200 w-full" placeholder="Type Shop Adderss/Name here...">
                        </div>
                        <div class="flex flex-col">
                            <label for="" class="ps-5">Date Scheduled</label>
                            <input type="text"
                            name="date_scheduled" id="date-picker" class="inputfield py-3 px-5 shadow-md shadow-gray-200">
                        </div>
                        <div class="hidden lg:flex flex-col">
                            <label for="" class="ps-5">Budget</label>
                            <input type="number"
                            name="budget" class="inputfield py-3 px-5 placeholder:italic shadow-md shadow-gray-200 w-32" placeholder="0">
                        </div>
                        <div class="hidden lg:flex flex-col">
                            <label for="" class="ps-5">Total</label>
                            <input type="number" name="number_of_items" class="inputfield py-3 px-5 placeholder:italic shadow-md shadow-gray-200 w-32" placeholder="0" value="0" readonly>
                        </div>
    
                        <div class="flex justify-between lg:hidden gap-5">
                            <div class="flex flex-col">
                                <label for="" class="ps-5">Budget</label>
                                <input type="number" name="budget" class="inputfield py-3 px-5 placeholder:italic shadow-md shadow-gray-200 w-full" placeholder="0" value="0">
                            </div>
                            <div class="flex flex-col">
                                <label for="" class="ps-5">Total</label>
                                <input type="number" name="number_of_items" class="inputfield py-3 px-5 placeholder:italic shadow-md shadow-gray-200 w-full" value="0" readonly>
                            </div>
                        </div>
                    </div>
    
                    <div>
                        <button type="button" class="bg-accent w-full primary-action-btn md:w-40" id="add-item">Add Item</button>
                    </div>
                </div>
    
                <hr class="border border-stone-300" />
            </div>
    
            <div class="items-plan-wrapper custom-scrollbar">
                <div class="text-center italic text-gray-500 no-item-message">No Items Yet.</div>
            </div>

        </form>
        <div id="item-template" style="display: none">
            <x-plan.create-item />
        </div>
    </x-layout>
@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr('#date-picker', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'F j, Y',
            defaultDate: 'today',
        });
    </script>
    <script>
        let itemCount = 0;
        let itemMessageEl = null;
        let submitButtonEl = null;
        $('#add-item').on('click', function() {
            itemCount++;
            if (!itemMessageEl) {
                itemMessageEl = $('.no-item-message').clone();
            }
            $('.no-item-message').remove();

            let clone = $('#item-template').children().first().clone();

            clone.find('input[name="item_name"]')
                .attr('name', `items[${itemCount}][name]`);
            clone.find('input[name="item_expected_quantity"]')
                .attr('name', `items[${itemCount}][expected_quantity]`);

                
            $('.items-plan-wrapper').append(clone);
            if (!submitButtonEl) {
                const submitBtn = $('<button type="submit">Create Plan</button>').attr({
                    class: 'bg-accent primary-action-btn w-5/6 md:w-40 rounded-xl fixed bottom-0 mb-6 self-center md:self-start disabled:opacity-50'
                });

                
                $('.items-plan-wrapper').append(submitBtn);

                submitButtonEl = submitBtn;
            }
        });
        
        $(document).on('click', '.remove-item-btn', function() {
            itemCount--;
            $(this).closest('.product-item').remove();
            
            sumUpItems();

            if (itemCount <= 0) {
                $('.items-plan-wrapper').append(itemMessageEl);

                $('.items-plan-wrapper > button').remove();
                submitButtonEl = null;
            }
        });

        $(document).on('input', '.expected-quantity-input', function() {
            let val = $(this).val();
            let parentItem = $(this).closest('.product-item');

            parentItem.find('.expected-quantity-input').val(val);
            sumUpItems();
        });

        function sumUpItems() {
            let total = 0;

            $('.product-item').each(function() {
                let val = parseFloat($(this).find('.expected-quantity-input').first().val()) || 0;
                total += val;
            });

            $('input[name="number_of_items"]').val(total);
        }

        $('input[name="budget"]').on('input', function() {
            let val = $(this).val();
            $('input[name="budget"]').val(val);
        });

        $(document).on('submit', '#storePlanForm', function(e) {
            e.preventDefault();
            const formData = new FormData($(this)[0]);
            $('button[type="submit"]').attr('disabled', true);
            
            $.ajax({
                url: '{{ route("plan.store") }}',
                type: 'POST',
                data: formData,
                processContent: false,
                success: function(response) {
                    console.log('response', response);
                    if (response.status == 'success') {
                        toastr.success(response.message);
                        setTimeout(() => {
                            window.location.href = @json(route('list'));
                        }, 1500);
                    } else {
                        $('button[type="submit"]').attr('disabled', false);
                    }
                },
                error: function(xhr) {
                    console.error('error', xhr.responseJSON);
                    $('button[type="submit"]').attr('disabled', false);
                    
                    const errors = xhr.responseJSON?.errors;
                    const message = xhr.responseJSON?.message;

                    if (Object.keys(errors).length > 0) {
                        const uniqueErrors = [
                            ...new Set(Object.values(errors).flat())
                        ].reverse();

                        uniqueErrors.forEach(item => {
                            toastr.error(item);
                        });
                    } else if (message) {
                        toastr.error(message);
                    }
                }
            });
        });
    </script>
@endpush