<?php
namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index()
    {
        return Profile::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required',
            'slug' => 'required|unique:profiles,slug',
        ]);

        return Profile::create($data);
    }
}
