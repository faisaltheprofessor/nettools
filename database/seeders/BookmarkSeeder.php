<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bookmark;
class BookmarkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sg1 = Bookmark::create(['name' => 'SG1', 'type' => 'folder']);
        $repo = Bookmark::create(['name' => 'Repositories', 'type' => 'folder', 'parent_id' => $sg1->id]);

        Bookmark::create([
            'name' => 'Checkmk',
            'type' => 'link',
            'url' => 'https://vs035.domain.ltd/domain/check_mk',
            'parent_id' => $sg1->id,
        ]);

        Bookmark::create([
            'name' => 'Repo1',
            'type' => 'link',
            'url' => 'http://repo01/',
            'parent_id' => $repo->id,
        ]);

        Bookmark::create([
            'name' => 'IP Datenbank - User',
            'type' => 'link',
            'url' => 'https://ip-admin.domain.ltd/login?next=/',
            'parent_id' => $sg1->id,
        ]);

        Bookmark::create([
            'name' => 'Bitwarden',
            'type' => 'link',
            'url' => 'https://admin-vault.domain.ltd',
            'icon' => 'icons/bitwarden.png', // optional
        ]);
    }
}
