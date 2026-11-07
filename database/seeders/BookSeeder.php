<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        // [titre, auteur, date, pages, langue, genre, sous-genre, résumé]
        $books = [
            ["Le Seigneur des anneaux", "J.R.R. Tolkien", "1954-07-29", 1216, "fr", "fantasy", "epic-fantasy", "Une communauté entreprend de détruire l'Anneau unique pour sauver la Terre du Milieu."],
            ["Le Hobbit", "J.R.R. Tolkien", "1937-09-21", 310, "fr", "fantasy", "epic-fantasy", "Bilbon Sacquet accompagne treize nains pour reprendre un trésor gardé par un dragon."],
            ["Harry Potter à l'école des sorciers", "J.K. Rowling", "1997-06-26", 320, "fr", "fantasy", null, "Un orphelin découvre à onze ans qu'il est sorcier et entre à Poudlard."],
            ["Le Nom du vent", "Patrick Rothfuss", "2007-03-27", 662, "fr", "fantasy", "epic-fantasy", "Kvothe raconte comment un garçon surdoué est devenu une légende."],
            ["Le Trône de fer", "George R.R. Martin", "1996-08-01", 694, "fr", "fantasy", "epic-fantasy", "Des familles nobles s'affrontent pour le pouvoir tandis qu'une menace grandit au nord."],
            ["Fourth Wing", "Rebecca Yarros", "2023-05-02", 512, "en", "fantasy", "romantasy", "Violet Sorrengail doit survivre à l'école des cavaliers de dragons."],
            ["Un palais d'épines et de roses", "Sarah J. Maas", "2015-05-05", 432, "fr", "fantasy", "romantasy", "Feyre est emmenée dans le royaume des Fées après avoir tué un loup."],
            ["Le Prince cruel", "Holly Black", "2018-01-02", 384, "fr", "fantasy", null, "Jude grandit à la cour des Fées et veut y gagner sa place malgré sa condition de mortelle."],

            ["Orgueil et Préjugés", "Jane Austen", "1813-01-28", 432, "fr", "romance", null, "Elizabeth Bennet et M. Darcy doivent dépasser leurs préjugés."],
            ["Avant toi", "Jojo Moyes", "2012-01-05", 480, "fr", "romance", "romance-contemporaine", "Louisa devient l'aide d'un jeune homme tétraplégique et sa vie change."],
            ["It Ends with Us", "Colleen Hoover", "2016-08-02", 384, "en", "romance", "romance-contemporaine", "Lily vit une histoire d'amour qui la confronte à son passé."],

            ["Haunting Adeline", "H.D. Carlton", null, null, "en", "dark-romance", null, "Une jeune femme est traquée par un homme qui prétend la protéger."],
            ["Corrupt", "Penelope Douglas", null, null, "en", "dark-romance", null, "Une rivalité universitaire se transforme en jeu dangereux."],

            ["Les Sept Morts d'Evelyn Hardcastle", "Stuart Turton", "2018-02-08", null, "fr", "thriller", null, "Un homme revit la même journée dans le corps de huit invités pour résoudre un meurtre."],
            ["Les Apparences", "Gillian Flynn", "2012-06-05", 432, "fr", "thriller", "thriller-psychologique", "Le jour de son anniversaire de mariage, Amy disparaît et son mari devient suspect."],
            ["La Patiente silencieuse", "Alex Michaelides", "2019-02-05", 336, "fr", "thriller", "thriller-psychologique", "Un psychothérapeute veut faire parler une patiente muette depuis le meurtre de son mari."],
            ["Le Silence des agneaux", "Thomas Harris", "1988-05-01", 367, "fr", "thriller", "thriller-psychologique", "Une jeune agente du FBI consulte un tueur emprisonné pour en traquer un autre."],
            ["Millénium 1 : Les hommes qui n'aimaient pas les femmes", "Stieg Larsson", "2005-08-01", null, "fr", "thriller", null, "Un journaliste et une hackeuse enquêtent sur une disparition vieille de quarante ans."],

            ["Dune", "Frank Herbert", "1965-08-01", 688, "fr", "science-fiction", "space-opera", "Paul Atréides et sa famille sont envoyés sur Arrakis, planète de l'épice."],
            ["Fondation", "Isaac Asimov", "1951-01-01", 255, "fr", "science-fiction", "space-opera", "Un mathématicien prévoit la chute de l'Empire galactique et fonde une institution pour sauver le savoir."],
            ["1984", "George Orwell", "1949-06-08", 328, "fr", "science-fiction", "dystopie", "Winston Smith vit sous la surveillance permanente de Big Brother."],
            ["Le Meilleur des mondes", "Aldous Huxley", "1932-01-01", 288, "fr", "science-fiction", "dystopie", "Dans une société conditionnée au bonheur, un homme refuse de se conformer."],
            ["Projet Dernière Chance", "Andy Weir", "2021-05-04", 476, "fr", "science-fiction", "space-opera", "Un homme amnésique se réveille seul dans un vaisseau avec une mission vitale."],
            ["Neuromancien", "William Gibson", "1984-07-01", 271, "fr", "science-fiction", null, "Un hacker déchu reçoit une dernière mission dans le cyberespace."],

            ["Shining", "Stephen King", "1977-01-28", 447, "fr", "horreur", null, "Un gardien d'hôtel isolé par l'hiver sombre dans la folie."],
            ["Ça", "Stephen King", "1986-09-15", 1138, "fr", "horreur", null, "Sept amis affrontent, enfants puis adultes, une entité qui hante leur ville."],
            ["Misery", "Stephen King", "1987-06-08", 320, "fr", "horreur", null, "Un écrivain blessé est séquestré par une admiratrice obsessionnelle."],
            ["Dracula", "Bram Stoker", "1897-05-26", 418, "fr", "horreur", null, "Un comte des Carpates s'installe en Angleterre et un groupe d'hommes tente de l'arrêter."],
            ["Frankenstein", "Mary Shelley", "1818-01-01", 280, "fr", "horreur", null, "Un jeune scientifique donne vie à une créature qu'il abandonne, avec des conséquences tragiques."],
        ];

        foreach ($books as [$title, $authorName, $date, $pages, $lang, $genreSlug, $subSlug, $synopsis]) {
            $book = Book::updateOrCreate(
                ['title' => $title],
                [
                    'synopsis' => $synopsis,
                    'publication_date' => $date,
                    'page_count' => $pages,
                    'language' => $lang,
                ]
            );

            $book->authors()->sync([Author::where('name', $authorName)->firstOrFail()->id]);

            // Genre principal, puis sous-genre éventuel
            $genres = [
                Genre::where('slug', $genreSlug)->firstOrFail()->id => ['is_primary' => true],
            ];
            if ($subSlug) {
                $genres[Genre::where('slug', $subSlug)->firstOrFail()->id] = ['is_primary' => false];
            }
            $book->genres()->sync($genres);
        }
    }
}
