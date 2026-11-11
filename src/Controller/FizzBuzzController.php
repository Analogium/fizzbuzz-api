<?php

namespace App\Controller;

use App\FizzBuzz;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FizzBuzzController extends AbstractController
{
    // Borne haute : évite qu'un client demande une réponse de plusieurs Mo.
    private const MAX_LIMIT = 10_000;

    #[Route('/api/fizzbuzz', name: 'api_fizzbuzz', methods: ['GET'])]
    public function __invoke(Request $request, FizzBuzz $fizzBuzz): JsonResponse
    {
        $limit = filter_var($request->query->get('limit', '100'), \FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => self::MAX_LIMIT],
        ]);

        if (false === $limit) {
            return $this->json(
                ['error' => \sprintf('Parameter limit must be an integer between 1 and %d.', self::MAX_LIMIT)],
                Response::HTTP_BAD_REQUEST,
            );
        }

        return $this->json(['limit' => $limit, 'result' => $fizzBuzz->generate($limit)]);
    }
}
