<?php

namespace App\Http\Controllers;

use App\Common\Content;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    // 公开:返回各说明文本渲染后的 HTML
    public function index()
    {
        return $this->success('content', Content::rendered());
    }

    // 公开:返回各提示的纯文本
    public function hints()
    {
        return $this->success('hints', Content::allHints());
    }

    // 管理:返回各文本原文与名称(供编辑)
    public function source()
    {
        return $this->success('content', [
            'values' => Content::all(),
            'labels' => Content::labels(),
            'hints' => Content::allHints(),
            'hintLabels' => Content::hintLabels(),
        ]);
    }

    // 管理:保存文本,保存后立即生效
    public function update(Request $request)
    {
        $content = $request->input('content');
        $hints = $request->input('hints');
        if (!is_array($content) && !is_array($hints)) {
            return $this->error('参数错误');
        }
        try {
            if (is_array($content)) {
                Content::put($content);
            }
            if (is_array($hints)) {
                Content::putHints($hints);
            }
        } catch (\Throwable $e) {
            return $this->error('保存失败: ' . $e->getMessage());
        }
        return $this->success('content', [
            'values' => Content::all(),
            'labels' => Content::labels(),
            'hints' => Content::allHints(),
            'hintLabels' => Content::hintLabels(),
        ]);
    }
}
