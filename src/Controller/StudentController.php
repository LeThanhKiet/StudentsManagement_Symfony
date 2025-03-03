<?php

namespace App\Controller;

use App\Entity\Student;
use App\Repository\StudentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class StudentController extends AbstractController
{
    #[Route('/student', name: 'app_student')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/StudentController.php',
        ]);
    }

    #[Route('/api/students', name: 'get_all_students', methods: ['GET'])]
    public function getAllStudents(StudentRepository $studentRepository): JsonResponse
    {
        $students = $studentRepository->findAll();

        return $this->json([
            'message' => 'All students',
            'data' => array_map(fn($student) => [
                'id'            => $student->getId(),
                'studentName'   => $student->getStudentName(),
                'age'           => $student->getAge(),
                'classroom'     => [
                    'id of class'   => $student->getClassroom()->getId(),
                    'name of class' => $student->getClassRoom()->getClassName()
                ]
            ], $students),
        ]);
    }


    public function getOneStudent(Student $student): JsonResponse
    {

    }


}
