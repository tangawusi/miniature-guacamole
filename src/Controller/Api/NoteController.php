<?php

namespace App\Controller\Api;

use App\Entity\Note;
use App\Entity\User;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/notes')]
class NoteController extends AbstractController
{
    #[Route('', name: 'api_notes_list', methods: ['GET'])]
    public function list(Request $request, NoteRepository $notes): JsonResponse
    {
        $user = $this->requireVerifiedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $query = $request->query->get('q');
        $status = $request->query->get('status');
        $category = $request->query->get('category');

        $results = $notes->searchForUser($user, $query, $status, $category);

        return $this->json([
            'notes' => array_map([$this, 'serializeNote'], $results),
            'filters' => [
                'statuses' => Note::STATUSES,
                'categories' => $notes->getCategoriesForUser($user),
            ],
        ]);
    }

    #[Route('', name: 'api_notes_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->requireVerifiedUser();
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $payload = json_decode($request->getContent(), true);

        $title = trim((string) ($payload['title'] ?? ''));
        $content = trim((string) ($payload['content'] ?? ''));
        $category = trim((string) ($payload['category'] ?? ''));
        $status = trim((string) ($payload['status'] ?? Note::STATUS_NEW));

        if ($title === '' || $content === '' || $category === '') {
            return $this->json(['message' => 'Title, content and category are required.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!in_array($status, Note::STATUSES, true)) {
            return $this->json(['message' => 'Status must be one of: new, todo, done.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $note = (new Note())
            ->setUser($user)
            ->setTitle($title)
            ->setContent($content)
            ->setCategory($category)
            ->setStatus($status);

        $entityManager->persist($note);
        $entityManager->flush();

        return $this->json([
            'message' => 'Note created successfully.',
            'note' => $this->serializeNote($note),
        ], JsonResponse::HTTP_CREATED);
    }

    private function requireVerifiedUser(): User|JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['message' => 'Authentication required.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!$user->isVerified()) {
            return $this->json(['message' => 'Please verify your account before using notes.'], JsonResponse::HTTP_FORBIDDEN);
        }

        return $user;
    }

    private function serializeNote(Note $note): array
    {
        return [
            'id' => $note->getId(),
            'title' => $note->getTitle(),
            'content' => $note->getContent(),
            'category' => $note->getCategory(),
            'status' => $note->getStatus(),
            'createdAt' => $note->getCreatedAt()->format(DATE_ATOM),
        ];
    }
}
