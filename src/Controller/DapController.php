<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
// use Twig\Environment;

use function Symfony\Component\String\u;

#[Route('/', name: 'app_')]
class DapController extends AbstractController
{
    #[Route(path: '/', name: 'homepage')]

    // public function homepage(Environment $twig): Response
    public function homepage(): Response
    {
        $zones = [
            ['ligne' => 'D41', 'zones' => 'Assemblage'],
            ['ligne' => 'Démontage', 'zones' => 'Assemblage'],
            ['ligne' => 'Enjoliveur', 'zones' => 'Assemblage'],
            ['ligne' => 'D41', 'zones' => 'Déchargement Reprise'],
            ['ligne' => 'DEFAUTHEQUE', 'zones' => 'Déchargement Reprise'],

            // 'Demontage - Assemblage',
            // 'Enjoliveur P51 - Assemblage',
            // 'P54 - Assemblage',
            // 'P8 MV - Assemblage',
            // 'Panneaux - Assemblage',
            // 'T9 MV - Assemblage',
            // 'D41 - Déchargement Reprise',
            // 'DEFAUTHEQUE - Déchargement Reprise',
            // 'GESTION-DR - Déchargement Reprise',
            // 'LUX-DR - Déchargement Reprise',
            // 'MATRICES-ILUO - Déchargement Reprise',
            // 'P87 - Déchargement Reprise',
        ];
        // Method to dump the specified variables : 
        // dump($zones);
        // dd($zones);

        return $this->render('Dap/homepage.html.twig', [
            'title' => 'DocAuPoste',
            'zones' => $zones,
        // $html = $twig->render('Dap/homepage.html.twig', [
        //     'title' => 'DocAuPoste',
        //     'zones' => $zones,
        ]);
        // dd($html);
        // return new Response($html);
    }

    // #[Route('/browse', name: 'browse')]
    // public function browse(): Response
    // {
    //     return new Response('Title : Doc Au Poste ptet, on verra :');
    // }
    #[Route('/browse/{slug}', name: 'browse')]
    public function browse(string $slug = null): Response
    {
        // $title = 'Doc Au Poste : '.$slug;
        // $title = str_replace('-', ' ', $slug);

        // if ($slug) {
        //     $title = 'zone : ' . u(str_replace('-', ' ', $slug))->title(true);
        // } else {
        //     $title = 'Doc Au Poste';
        // }
        $zone = $slug ? u(str_replace('-', ' ', $slug))->title(true) : null;
        

        // return new Response($title);
        return $this->render('Dap/browse.html.twig', [
            // 'title' => $title,
            'zone' => $zone,
        ]);
    }

        // $title = u(str_replace('-', ' ', $slug))->title(true);

        // return new Response('Title : Doc Au Poste pour '.$title);
    

}