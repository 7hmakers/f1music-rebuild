<?php

namespace App\Console\Commands;

use App\Common\Content;
use Illuminate\Console\Command;

class PublishContent extends Command
{
    protected $signature = 'content:publish';

    protected $description = '将内置的默认说明文本写入 config/content 目录(仅补全缺失的文件)';

    public function handle(): int
    {
        $created = Content::publish();

        if (empty($created)) {
            $this->info('所有内容文件均已存在,未做修改。');
        } else {
            $this->info('已创建: ' . implode(', ', $created));
        }

        $this->line('内容目录: ' . Content::dir());

        return self::SUCCESS;
    }
}
