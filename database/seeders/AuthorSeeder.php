<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Seeder;

class AuthorSeeder extends Seeder
{
    public function run(): void
    {
        // [nom, nationalité, date de naissance (ou null), biographie courte]
        $authors = [
            ['J.R.R. Tolkien', 'Britannique', '1892-01-03', "Écrivain, poète et philologue, auteur du Hobbit et du Seigneur des anneaux."],
            ['J.K. Rowling', 'Britannique', '1965-07-31', "Autrice de la saga Harry Potter."],
            ['Patrick Rothfuss', 'Américain', '1973-06-06', "Auteur de la série Chronique du Tueur de roi."],
            ['George R.R. Martin', 'Américain', '1948-09-20', "Auteur de la saga Le Trône de fer."],
            ['Rebecca Yarros', 'Américaine', null, "Autrice de romantasy, connue pour la série Empyrean."],
            ['Sarah J. Maas', 'Américaine', null, "Autrice de fantasy, connue pour les séries Throne of Glass et Un palais d'épines et de roses."],
            ['Holly Black', 'Américaine', null, "Autrice de fantasy jeunesse, connue pour Le Prince cruel."],
            ['Jane Austen', 'Britannique', '1775-12-16', "Romancière, autrice d'Orgueil et Préjugés."],
            ['Jojo Moyes', 'Britannique', null, "Romancière, autrice d'Avant toi."],
            ['Colleen Hoover', 'Américaine', null, "Autrice de romans sentimentaux, dont It Ends with Us."],
            ['H.D. Carlton', 'Américaine', null, "Autrice de dark romance, connue pour Haunting Adeline."],
            ['Penelope Douglas', 'Américaine', null, "Autrice de romances sombres, dont la série Devil's Night."],
            ['Stuart Turton', 'Britannique', null, "Auteur de thrillers, dont Les Sept Morts d'Evelyn Hardcastle."],
            ['Gillian Flynn', 'Américaine', null, "Autrice de thrillers, dont Les Apparences (Gone Girl)."],
            ['Alex Michaelides', 'Britannique', null, "Auteur du thriller La Patiente silencieuse."],
            ['Thomas Harris', 'Américain', null, "Auteur du Silence des agneaux."],
            ['Stieg Larsson', 'Suédois', '1954-08-15', "Journaliste et écrivain, auteur de la trilogie Millénium."],
            ['Frank Herbert', 'Américain', '1920-10-08', "Auteur du cycle de Dune."],
            ['Isaac Asimov', 'Américain', null, "Auteur de science-fiction, connu pour le cycle Fondation."],
            ['George Orwell', 'Britannique', '1903-06-25', "Écrivain et essayiste, auteur de 1984."],
            ['Aldous Huxley', 'Britannique', '1894-07-26', "Écrivain, auteur du Meilleur des mondes."],
            ['Andy Weir', 'Américain', null, "Auteur de science-fiction, connu pour Seul sur Mars et Projet Dernière Chance."],
            ['William Gibson', 'Américano-canadien', null, "Auteur de Neuromancien, roman fondateur du cyberpunk."],
            ['Stephen King', 'Américain', '1947-09-21', "Auteur américain de romans d'horreur et de fantastique."],
            ['Bram Stoker', 'Irlandais', '1847-11-08', "Auteur de Dracula."],
            ['Mary Shelley', 'Britannique', '1797-08-30', "Autrice de Frankenstein."],
        ];

        foreach ($authors as [$name, $nationality, $birthDate, $bio]) {
            Author::updateOrCreate(
                ['name' => $name],
                ['nationality' => $nationality, 'birth_date' => $birthDate, 'bio' => $bio]
            );
        }
    }
}
