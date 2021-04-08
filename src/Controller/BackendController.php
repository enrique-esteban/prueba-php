<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Job;
use App\Entity\Category;
//use Symfony\Component\Routing\Annotation\Route;

class BackendController extends AbstractController
{
    public function index(): Response
    {
        //NOTA: Obtengo los datos de la BD desde Doctrine, sin embargo adjunto la sentencia MYSQL 
        //      que usaría para obtener los datos si prescindiera del ORM:
        //         SELECT job.name, GROUP_CONCAT(category.name)
        //         FROM job
        //         LEFT JOIN jobs_categories ON jobs_categories.job_id = job.id
        //         LEFT JOIN category ON jobs_categories.category_id = category.id
        //         GROUP BY job.id;
        $jobs = $this->getDoctrine()->getRepository(Job::class)->findAll();

        dump($jobs);

        return $this->render('backend/index.html.twig', [
            'jobs' => $jobs
        ]);
    }

    public function ajaxSaveJob (Request $request)
    {
        //dump($request->isXMLHttpRequest());
        if ($request->isXMLHttpRequest()) {         
            $jobArray = $request->request->get('job');

            $jobInclude = $this->getDoctrine()->getRepository(Job::class)->findOneBy(['name' => $jobArray['name']]);
            
            if (!isset($jobInclude)) {
                $job = new Job();

                $job->setName($jobArray['name']);

                if (isset($jobArray['categories'])) {
                    foreach ($jobArray['categories'] as $value) {
                        $category = new Category();
                        $category = $this->getDoctrine()->getRepository(Category::class)->findOneBy(['name' => $value]);
                        $job->addCategory($category);
                    }
                }


                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($job);
                $entityManager->flush();
                
                // Metodo alternativo usando mysqli nativo php
                /* $connect = new \mysqli("localhost", "root", "root", "prueba");
                if ($connect->connect_error) {
                    die("Connection failed: " . $connect->connect_error);
                }
                
                $sql = "INSERT INTO Job VALUES (".rand().", '".$jobArray['name']."', '".$jobArray['name']."')";

                if (mysqli_query($connect, $sql)) {
                echo "Registro ingresado correctamente";
                } else {
                echo "Error: " . $sql . "" . mysqli_error($connect);
                }
                $connect->close();
                */
            }

            return new JsonResponse([ 'data' => $job, 'error' => false ]);
        }
        else {
            $response = new Response();
            $response->setContent(json_encode(array(
                'data' => 123,
            )));
            $response->headers->set('Content-Type', 'application/json');
            //return new response([ 'data' => "", 'error' => false ]);
        }
    }

    public function ajaxRemoveJob (Request $request)
    {
        if ($request->isXMLHttpRequest()) {         
            $jobId = $request->request->get('jobId');
            
            $job = $this->getDoctrine()->getRepository(Job::class)->findOneBy(['id' => $jobId]);

            dump($job);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($job);
            $entityManager->flush();
            
            return new JsonResponse($job);
        }
    }
}
