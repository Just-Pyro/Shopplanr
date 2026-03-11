@extends('layouts.app')
@section('content')
    <x-layout>
        <div class="p-3 md:p-6 2xl:px-72">
            <div class="border border-gray-50 shadow bg-white rounded-xl p-3 md:p-6 flex flex-col md:flex-row gap-3 md:gap-6">
                <div class="profile w-16 h-16 rounded-full border border-gray-200 bg-gray-200 self-center flex justify-center items-center text-3xl font-bold main-text">
                    {{ strtoupper(auth()->user()->first_name[0] . auth()->user()->last_name[0]) }}
                </div>
                <div class="flex-1 flex flex-col justify-between py-1 self-center">
                    <p class="text-2xl main-text text-center md:text-left">
                        {{ ucwords(auth()->user()->full_name) }}
                    </p>
                    <p class="italic text-gray-400">{{ auth()->user()->email }}</p>
                </div>
                <div class="flex flex-col justify-end pb-1 self-center">
                    <p class="italic text-gray-400">v1.0</p>
                </div>
            </div>
        </div>
    </x-layout>
@endsection