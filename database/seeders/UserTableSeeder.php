<?php

/** @noinspection DuplicatedCode */

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\CauserResolver;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

use function fake;

class UserTableSeeder extends Seeder
{
    /**
     * Populates the `patients` table with data fetched from the Rick and Morty API.
     *
     * This method truncates the existing `patients` table, fetches character data from
     * the API in pages, and processes them until 100 unique characters with names
     * containing at least two words are collected. The collected data is then used to
     * create patient records in the database, with avatars stored in the public disk.
     * Associated user and created at timestamps are randomly generated during the process.
     *
     * @throws GuzzleException
     */
    public function run() : void
    {
        $admin_user = User::factory()
            ->create([
                'role'       => UserRole::Admin,
                'first_name' => 'Doofus',
                'last_name'  => 'Rick',
                'email'      => 'doofus.rick@example.com',
                'created_at' => '2020-01-01 00:00:00',
            ]);

        $admin_user->assignRole('Admin');

        $this->addMedia($admin_user, 'https://rickandmortyapi.com/api/character/avatar/103.jpeg');

        // get the characters from the API
        $characters = $this->getCharacters();

        // split into two arrays
        $users = $characters->slice(0, 25);
        $characters->slice(25, 100);

        echo "\nAdding Users\n";

        // create the users
        $users->each(function ($character, $index) use ($admin_user) {

            // generate the user and set the causer resolver
            CauserResolver::setCauser($admin_user);
            $created_at = fake()->dateTimeBetween($admin_user->created_at, '-1 year');

            $name_parts = collect(explode(' ', $character['name']));
            $first_name = str($name_parts->shift())->title();
            $last_name = str($name_parts->pop())->title();

            $role = match (true) {
                $index <= 3  => UserRole::Admin,
                $index <= 7  => UserRole::Instructor,
                default      => UserRole::Student,
            };

            $role_name = match ($role) {
                UserRole::Admin => 'Admin',
                UserRole::Instructor => 'Instructor',
                default => 'Student',
            };

            $staff_user = User::factory()
                ->create([
                    'role'          => $role,
                    'first_name'    => $first_name,
                    'last_name'     => $last_name,
                    'email'         => str($first_name.'.'.$last_name.rand(100, 999).'@example.com')
                        ->lower()
                        ->remove([
                            ' ',
                            '\'',
                        ]),
                    'created_by_id' => $admin_user,
                    'updated_by_id' => $admin_user,
                    'created_at'    => $created_at,
                    'updated_at'    => $created_at,
                ]);

            $staff_user->assignRole($role_name);

            $this->addMedia($staff_user, $character['image']);
            echo '.';
        });


        echo "\n";

    }

    /**
     * @throws GuzzleException
     */
    private function getCharacters() : ?Collection
    {

        $client = new Client;
        $characters = collect();
        $page = 1;

        $character_json_path = database_path('src/characters.json');
        if (file_exists($character_json_path)) {
            $data = collect(json_decode(file_get_contents($character_json_path), true));
        } else {
            $response = $client->get('https://rickandmortyapi.com/api/character?page='.$page);
            $data = collect(json_decode($response->getBody(), true)['results']);
        }

        $new_characters = $data
            ->filter(function ($character) {
                return count(explode(' ', $character['name'])) >= 2
                       && !FilterData::hasBadWords($character['name']);
            })
            // just to be safe in case the above doesn't work
            ->map(function ($character) {
                $character['name'] = str($character['name'])
                    ->replace([
                        'Mrs.',
                        'Mr.',
                        'Dr.',
                    ], [
                        'Missus',
                        'Mister',
                        'Doctor',
                    ])
                    ->replaceMatches('/[.()]/', '');

                return $character;
            });

        $characters = $characters->merge($new_characters)
            ->unique('name')
            ->shuffle();

        if (!isset($data['info']['next'])) {
            return $characters;
        }

        return null;
    }

    private function addMedia($model, $image) : void
    {
        $avatar_path = database_path('avatars/'.md5($image).'.jpeg');

        if (!file_exists($avatar_path)) {

            if (!is_dir(dirname($avatar_path))) {
                mkdir(dirname($avatar_path), 0755, true);
            }
            try {
                $client = new Client;
                $response = $client->get($image);
                file_put_contents($avatar_path, $response->getBody());
            } catch (GuzzleException $e) {
                echo $e->getMessage();
                return;
            }
        }

        try {
            $model->addMedia($avatar_path)
                ->preservingOriginal()
                ->toMediaCollection('avatars');
        } catch (FileDoesNotExist|FileIsTooBig $e) {
            echo $e->getMessage().PHP_EOL;
        }
    }
}
