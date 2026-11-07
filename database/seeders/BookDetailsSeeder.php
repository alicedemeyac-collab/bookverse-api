<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;

class BookDetailsSeeder extends Seeder
{
    public function run(): void
    {
        $publishers = [
            "Le Seigneur des anneaux" => "Christian Bourgois",
            "Le Hobbit" => "Christian Bourgois",
            "Harry Potter à l'école des sorciers" => "Gallimard Jeunesse",
            "Le Nom du vent" => "Bragelonne",
            "Le Trône de fer" => "Pygmalion",
            "Fourth Wing" => "Red Tower Books",
            "Un palais d'épines et de roses" => "Hachette Romans",
            "Le Prince cruel" => "Rageot",
            "Orgueil et Préjugés" => "Gallimard",
            "Avant toi" => "Milady",
            "It Ends with Us" => "Atria Books",
            "Les Sept Morts d'Evelyn Hardcastle" => "Sonatine",
            "Les Apparences" => "Sonatine",
            "La Patiente silencieuse" => "Sonatine",
            "Le Silence des agneaux" => "Pocket",
            "Millénium 1 : Les hommes qui n'aimaient pas les femmes" => "Actes Sud",
            "Dune" => "Robert Laffont",
            "Fondation" => "Denoël",
            "1984" => "Gallimard",
            "Le Meilleur des mondes" => "Plon",
            "Projet Dernière Chance" => "Bragelonne",
            "Neuromancien" => "J'ai lu",
            "Shining" => "Albin Michel",
            "Ça" => "Albin Michel",
            "Misery" => "Albin Michel",
            "Dracula" => "Le Livre de Poche",
            "Frankenstein" => "Gallimard",
        ];

        foreach ($publishers as $title => $publisher) {
            Book::where('title', $title)->update(['publisher' => $publisher]);
        }

        // ISBN-13 fictifs de démonstration, à remplacer par les vrais lors de l'import
        $isbns = [
            'Dune' => '9790000000001',
            'Le Hobbit' => '9790000000018',
            'Dracula' => '9790000000025',
        ];

        foreach ($isbns as $title => $isbn) {
            Book::where('title', $title)->update(['isbn13' => $isbn]);
        }
    }
}
