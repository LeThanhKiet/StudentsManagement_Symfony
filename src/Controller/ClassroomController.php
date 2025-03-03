<?php

namespace App\Controller;

use App\Entity\Classroom;
use App\Repository\ClassroomRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class ClassroomController extends AbstractController
{
    #[Route('/classroom', name: 'app_classroom')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message'   => 'Welcome to your new controller!',
            'path'      => 'src/Controller/ClassroomController.php',
        ]);
    }

    #[Route('/api/classroom', name: 'get_all_classroom', methods: ['GET'])]
    public function getAllClassrooms(ClassroomRepository $classroomRepository): JsonResponse
    {
        $classrooms = $classroomRepository->getAllClassroom();

        return $this->json([
            'message' => 'All classrooms',
            'data' => array_map(fn($classroom) => [
                'id'                => $classroom->getId(),
                'className'         => $classroom->getClassName(),
                'numberOfStudents'  => $classroom->getNumberOfStudents(),
            ], $classrooms),
        ]);
    }

    #[Route('/api/classroom/{id}', name: 'get_classroom_byID', methods: ['GET'])]
    public function getClassroomById(ClassroomRepository $classroomRepository, int $id): JsonResponse
    {
        $classroom = $classroomRepository->find($id);
        if (!$classroom) {
            return $this->json([
                'message' => 'Classroom not found',
                'data' => null
            ], 404);
        }

        return $this->json([
            'message' => 'Classroom found',
            'data' => [
                'id'                => $classroom->getId(),
                'className'         => $classroom->getClassName(),
                'numberOfStudents'  => $classroom->getNumberOfStudents(),
            ]
        ]);
    }

    #[Route("/api/createClassroom", name: "create_classroom", methods: ["POST"])]
    public function createClassroom(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['className']) || !isset($data['numberOfStudents'])) {
            return $this->json([
                'message' => 'Invalid data'
            ], 400);
        }

        $classRoom = new Classroom();
        $classRoom->setClassName($data["className"]);
        $classRoom->setNumberOfStudents($data["numberOfStudents"]);

        $entityManager->persist($classRoom);
        $entityManager->flush();

        return $this->json([
            'message' => 'Classroom created successfully',
            'data' => [
                'id'                => $classRoom->getId(),
                'className'         => $classRoom->getClassName(),
                'numberOfStudents'  => $classRoom->getNumberOfStudents(),
            ],
        ], 201);
    }

    #[Route("/api/editClassroom/{id}", name: "edit_classroom", methods: ["PUT"])]
    public function editClassroom(Request $request, ClassroomRepository $classroomRepository, EntityManagerInterface $entityManager,int $id): JsonResponse
    {
        $classroom = $classroomRepository->find($id);
        if(!$classroom) {
            return $this->json([
                'message' => 'Classroom not found',
            ], 404);
        }

        $data = json_decode($request->getContent(), true);
        if(isset($data['className'])) {
            $classroom->setClassName($data['className']);
        }

        if(isset($data['numberOfStudents'])) {
            $classroom->setNumberOfStudents($data['numberOfStudents']);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Classroom edited successfully',
            'data' => [
                'id'                => $classroom->getId(),
                'className'         => $classroom->getClassName(),
                'numberOfStudents'  => $classroom->getNumberOfStudents(),
            ]
        ], 201);
    }

    #[Route("/api/deleteClassroom/{id}", name: "delete_classroom", methods: ["DELETE"])]
    public function deleteClassroom(ClassroomRepository $classroomRepository, EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $classroom = $classroomRepository->find($id);
        if(!$classroom) {
            return $this->json([
                'message' => 'Classroom not found',
            ], 404);
        }

        $entityManager->remove($classroom);
        $entityManager->flush();
        return $this->json([
            'message' => 'Classroom deleted successfully',
        ], 201);
    }
}
