<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $genres = [
            [
                'name' => 'Romance', 'slug' => 'romance',
                'description' => "Histoires d'amour, de rencontres et d'émotions, des classiques aux comédies romantiques.",
                'primary_color' => '#E8A0B4', 'secondary_color' => '#FFF1EC', 'accent_color' => '#C9A98C',
                'heading_font' => 'Playfair Display', 'body_font' => 'Lato',
                'mood' => 'Douce, pastel, lumineuse',
                'children' => [
                    ['name' => 'Romance contemporaine', 'slug' => 'romance-contemporaine',
                        'description' => "Histoires d'amour ancrées dans notre époque."],
                ],
            ],
            [
                'name' => 'Fantasy', 'slug' => 'fantasy',
                'description' => "Royaumes imaginaires, magie, créatures et quêtes légendaires.",
                'primary_color' => '#6D3FC4', 'secondary_color' => '#0F1535', 'accent_color' => '#D4AF37',
                'heading_font' => 'Cinzel', 'body_font' => 'Lora',
                'mood' => 'Magique, mystérieuse',
                'children' => [
                    ['name' => 'Epic Fantasy', 'slug' => 'epic-fantasy',
                        'description' => "Grandes épopées, guerres et destins de mondes entiers."],
                    ['name' => 'Romantasy', 'slug' => 'romantasy',
                        'description' => "La fantasy où la romance occupe le premier plan."],
                ],
            ],
            [
                'name' => 'Dark Romance', 'slug' => 'dark-romance',
                'description' => "Romances intenses et sombres, aux relations ambiguës et dangereuses.",
                'primary_color' => '#7A1F2B', 'secondary_color' => '#0B0709', 'accent_color' => '#B3122B',
                'heading_font' => 'Cormorant Garamond', 'body_font' => 'Inter',
                'mood' => 'Sensuelle, intense',
                'children' => [],
            ],
            [
                'name' => 'Thriller', 'slug' => 'thriller',
                'description' => "Enquêtes, tension et rebondissements qui tiennent en haleine jusqu'à la dernière page.",
                'primary_color' => '#8A8D91', 'secondary_color' => '#121315', 'accent_color' => '#D62828',
                'heading_font' => 'Oswald', 'body_font' => 'Inter',
                'mood' => 'Tendue, froide',
                'children' => [
                    ['name' => 'Thriller psychologique', 'slug' => 'thriller-psychologique',
                        'description' => "Le suspense naît de l'esprit des personnages."],
                ],
            ],
            [
                'name' => 'Science-fiction', 'slug' => 'science-fiction',
                'description' => "Futurs possibles, technologies, espace et questions sur l'humanité.",
                'primary_color' => '#2563EB', 'secondary_color' => '#070B1F', 'accent_color' => '#22D3EE',
                'heading_font' => 'Orbitron', 'body_font' => 'Rajdhani',
                'mood' => 'Futuriste, technologique',
                'children' => [
                    ['name' => 'Space opera', 'slug' => 'space-opera',
                        'description' => "Aventures à l'échelle des galaxies."],
                    ['name' => 'Dystopie', 'slug' => 'dystopie',
                        'description' => "Sociétés futures oppressantes et avertissements sur notre présent."],
                ],
            ],
            [
                'name' => 'Horreur', 'slug' => 'horreur',
                'description' => "Peur, angoisse et surnaturel : des classiques gothiques aux cauchemars modernes.",
                'primary_color' => '#3A0A0A', 'secondary_color' => '#080606', 'accent_color' => '#8B0000',
                'heading_font' => 'Special Elite', 'body_font' => 'Inter',
                'mood' => 'Oppressante, sombre',
                'children' => [],
            ],
        ];

        $themeKeys = [
            'primary_color', 'secondary_color', 'accent_color',
            'heading_font', 'body_font', 'mood',
        ];

        foreach ($genres as $data) {
            $children = Arr::pull($data, 'children');

            $parent = Genre::updateOrCreate(['slug' => $data['slug']], $data);

            foreach ($children as $child) {
                Genre::updateOrCreate(
                    ['slug' => $child['slug']],
                    $child + Arr::only($data, $themeKeys) + ['parent_id' => $parent->id]
                );
            }
        }
    }
}
