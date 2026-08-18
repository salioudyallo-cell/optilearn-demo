<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LegalController extends Controller
{
    public function terms(): View
    {
        return view('public.legal.terms');
    }

    public function notice(): View
    {
        return view('public.legal.notice');
    }

    public function privacy(): View
    {
        return view('public.legal.privacy');
    }
}
