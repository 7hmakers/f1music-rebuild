<?php

namespace App\Http\Controllers;

use App\Common\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function index()
    {
        $admins = User::where('permission', '>=', Permission::Admin->value)
            ->get(['id', 'name', 'permission']);
        return $this->success('admins', $admins);
    }

    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'id' => 'required | string | size:11',
            'name' => 'nullable | string'
        ], [
            'id.required' => '请输入学号',
            'id.size' => '学号应为11位'
        ])->validate();

        $id = $request->input('id');
        $user = User::firstOrNew(['id' => $id]);
        if ($request->filled('name')) {
            $user->name = $request->input('name');
        }
        $user->permission = Permission::Admin->value;
        $user->save();

        return $this->success('admin', $user->only(['id', 'name', 'permission']));
    }

    public function destroy(Request $request)
    {
        Validator::make($request->all(), [
            'id' => 'required | string | size:11'
        ], [
            'id.required' => '请选择要移除的管理员',
            'id.size' => '学号应为11位'
        ])->validate();

        $id = $request->input('id');
        if (Auth::id() === $id) {
            return $this->error('不能移除自己的管理员权限');
        }
        $user = User::find($id);
        if (empty($user)) {
            return $this->error('用户不存在');
        }
        if ($user->permission < Permission::Admin->value) {
            return $this->error('该用户不是管理员');
        }
        if (User::where('permission', '>=', Permission::Admin->value)->count() <= 1) {
            return $this->error('至少需要保留一名管理员');
        }
        $user->permission = Permission::User->value;
        $user->save();
        return $this->success();
    }
}
