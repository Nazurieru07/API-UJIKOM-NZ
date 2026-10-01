<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index() : JsonResponse
    {
        $users = User::latest()->get();
        return response()->json([
            'message'   => 'Daftar pengguna berhasil diambil.',
            'data'      => UserResource::collection($users)
        ]);
    }
   
    public function store(StoreUserRequest $request) : JsonResponse
    {
        $data = $request->validated();
        $user = DB::transaction(function () use ($request, $data) {
            $data['password'] = Hash::make($data['password']);

            if ($request->hasFile('foto_profile')) {
                $data['foto_profile'] = $request->file('foto_profile')->store('profiles', 'public');
            }
            return User::create($data);
        });
        return response()->json([
            'message'   => 'Pengguna berhasil ditambahkan.',
            'data'      => new UserResource($user)
        ], 201);
    }

    public function show(User $user) : JsonResponse
    {
        return response()->json([
            'data'  => new UserResource($user)
        ]);
    }

    public function update(UpdateUserRequest $request, User $user) : JsonResponse
    {
        $data = collect($request->validated());
        DB::transaction(function () use ($request, $data, $user) {
            if ($data->get('password')) {
                $data['password'] = Hash::make($data['password']);
            } else {
                $data->forget('password');
            }

            if ($request->hasFile('foto_profile')) {
                if ($user->foto_profile) {
                    Storage::disk('public')->delete($user->foto_profile);
                }
                $data['foto_profile'] = $request->file('foto_profile')->store('profiles', 'public');
            }
            $user->update($data->toArray());
        });
        return response()->json([
            'message'   =>  'Data pengguna berhasil diperbarui.',
            'data'      => new UserResource($user)
        ]);
    }

    public function destroy(User $user) : JsonResponse
    {
        DB::transaction(function () use ($user) {
            if ($user->foto_profile) {
                Storage::disk('public')->delete($user->foto_profile);
            }
            $user->delete();
        });
        return response()->json([
            'message'   => 'Pengguna berhasil dihapus.'
        ]);
    }
}