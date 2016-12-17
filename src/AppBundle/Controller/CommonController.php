<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Phpml\Classification\KNearestNeighbors;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Phpml\Regression\LeastSquares;

class CommonController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $leastSquaresForm = $this->createFormBuilder()
            ->add('data-file', FileType::class, array('label' => 'Train data-file (csv)', 'required' => true))
            ->add('data-sample', TextType::class, array('label' => 'Value', 'required' => true))
            ->getForm();

        $kNearestNeighborsForm = $this->createFormBuilder()
            ->add('data-file', FileType::class, array('label' => 'Train data-file (csv)', 'required' => true))
            ->add('data-sample', TextType::class, array('label' => 'Value', 'required' => true))
            ->getForm();

        $leastSquaresForm->handleRequest($request);

        if ($leastSquaresForm->isSubmitted() && $leastSquaresForm->isValid()) {
            $data = $leastSquaresForm->getData();

            $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
            
            $fileName = md5(uniqid()).'.csv';

            $data['data-file']->move(
                'files',
                $fileName
            );

            $samples = $serializer->decode(file_get_contents('files/'.$fileName), 'csv');
            $train = [];
            foreach($samples[0] as $key => $value)
            {
                $train[] = [$samples[1][$key], $samples[2][$key]];
            }

            $regression = new LeastSquares();
            $regression->train($train, $samples[0]);

            $leastSquaresResult = $regression->predict(explode(',', $data['data-sample']));
        }


        if ($kNearestNeighborsForm->isSubmitted() && $kNearestNeighborsForm->isValid()) {
            $data = $kNearestNeighborsForm->getData();

            $serializer = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
            
            $fileName = md5(uniqid()).'.csv';

            $data['data-file']->move(
                'files',
                $fileName
            );

            $samples = $serializer->decode(file_get_contents('files/'.$fileName), 'csv');
            $train = [];
            foreach($samples[0] as $key => $value)
            {
                $train[] = [$samples[1][$key], $samples[2][$key]];
            }

            $classifier = new KNearestNeighbors();
            $classifier->train($train, $samples[0]);

            $kNearestNeighborsResult = $classifier->predict(explode(',', $data['data-sample']));
        }

        return $this->render('common/index.html.twig', array(
            'least_squares_form' => !$leastSquaresForm->getData() ? $leastSquaresForm->createView() : "",
            'knearest_neighbors_form' => !$kNearestNeighborsForm->getData() ? $kNearestNeighborsForm->createView() : "",
            'least_squares_result' => $leastSquaresResult ?? "",
            'knearest_neighbors_result' => $kNearestNeighborsResult ?? ""
        ));
    }
}
