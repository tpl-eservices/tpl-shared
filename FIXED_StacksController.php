<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StacksController extends Controller
{
    /**
     * Display the stacks index page.
     *
     * User is guaranteed to be authenticated via BiblioCommons
     * because of the 'biblio.auth' middleware.
     */
    public function index()
    {
        // Get authenticated user from biblio guard
        // User data is fetched fresh from BiblioCommons API
        $user = Auth::guard('biblio')->user();

        return inertia('Stacks/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Store a new stack.
     */
    public function store(Request $request)
    {
        $user = Auth::guard('biblio')->user();

        // Your store logic here...

        return redirect()->route('home');
    }
}
