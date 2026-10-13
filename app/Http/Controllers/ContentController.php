<?php

namespace App\Http\Controllers;

use App\Common\Content;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    // 公开:返回各文本渲染后的 HTML
    public function index()
    {
        return $this->success('content', Content::rendered());
    }

    // 管理:返回各文本的 Markdown 原文与名称(供编辑)
    public function source()
    {
        return $this->success('content', [
            'values' => Content::all(),
            'labels' => Content::labels(),
        ]);
    }

    // 管理:保存 Markdown,保存后立即生效
    public function update(Request $request)
    {
        $content = $request->input('content');
        if (!is_array($content)) {
            return $this->error('参数错误');
        }
        try {
            Content::put($content);
        } catch (\Throwable $e) {
            return $this->error('保存失败: ' . $e->getMessage());
        }
        return $this->success('content', [
            'values' => Content::all(),
            'labels' => Content::labels(),
        ]);
    }
}
