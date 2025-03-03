<?php

namespace App\Controller;

use App\Entity\Classroom;
use App\Entity\Student;
use App\Repository\ClassroomRepository;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
                'id'                => $student->getId(),
                'studentName'       => $student->getStudentName(),
                'age'               => $student->getAge(),
                'classroom'         => [
                        'id'        => $student->getClassroom()?->getId(),
                        'className' => $student->getClassRoom()?->getClassName()
                ]
            ], $students),
        ]);
    }

    #[Route('/api/student/{id}', name: 'get_student_byID', methods: ['GET'])]
    public function getOneStudent(StudentRepository $studentRepository, int $id): JsonResponse
    {
        $student = $studentRepository->find($id);
        if(!$student) {
            return $this->json([
                'message' => 'Student not found',
                'data'    => null
            ], 404);
        }

        return $this->json([
            'message' => 'Student found',
            'data' => [
                'id'                => $student->getId(),
                'studentName'       => $student->getStudentName(),
                'age'               => $student->getAge(),
                'classroom'         => [
                    'id'            => $student->getClassroom()?->getId(),
                    'className'     => $student->getClassRoom()?->getClassName()
                ]
            ]
        ]);
    }

    #[Route('/api/create-student', name: 'create_student', methods: ['POST'])]
    public function createStudent(Request $request, ClassroomRepository $classroomRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['studentName']) || !isset($data['age']) || !isset($data['classRoom'])) {
            return $this->json([
                'message' => 'Invalid data'
            ], 400);
        }

        // Check classroom ìnfo request vs clasroom in DB
        $classroom = $classroomRepository->findOneBy([
            'id' => $data['classRoom']['id'],
            'className' => $data['classRoom']['className']
        ]);


        if (!$classroom) {
            return $this->json([
                'message' => 'Classroom not found'
            ], 404);
        }
        $student = new Student();
        $student->setStudentName($data['studentName']);
        $student->setAge($data['age']);
        $student->setClassroom($classroom);

        $entityManager->persist($student);
        $entityManager->flush();

        return $this->json([
            'message' => 'Student created',
            'data' => [
                'id'                => $student->getId(),
                'studentName'       => $student->getStudentName(),
                'age'               => $student->getAge(),
                'classRoom'         => [
                    'id'            => $student->getClassroom()?->getId(),
                    'className'     => $student->getClassRoom()?->getClassName()
                ]
            ]
        ]);
    }

    #[Route('/api/update-student/{id}', name: 'update_student', methods: ['PUT'])]
    public function editStudent(Request $request, StudentRepository $studentRepository, EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $student = $studentRepository->find($id);
        if(!$student) {
            return $this->json([
                'message' => 'Student not found',
            ], 404);
        }

        $data = json_decode($request->getContent(), true);
        if(isset($data["studentName"])) { // isset() check variable to != null
            $student->setStudentName($data['studentName']);
        }

        if(isset($data["age"])) {
            $student->setAge($data['age']);
        }

        if(isset($data["classRoom"])) {
            $classroomRepository = $entityManager->getRepository(Classroom::class);
            $classroom = $classroomRepository->find($data['classRoom']['id']);
            if(!$classroom) {
                return $this->json([
                   'message' => 'Classroom not exist'
                ]);
            }
            $student->setClassroom($classroom);
        }

        $entityManager->flush();
        return $this->json([
            'message' => 'Student updated',
            'data' => [
                'id'                => $student->getId(),
                'studentName'       => $student->getStudentName(),
                'age'               => $student->getAge(),
                'classRoom'         => [
                    'id'            => $student->getClassroom()?->getId(),
                    'className'     => $student->getClassRoom()?->getClassName()
                ]
            ]
        ]);

    }
}
