<?php
namespace Drupal\course_sign_up\Controller;
 
use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
 
class SignUpPageController extends ControllerBase {

  /**
   * Database connection
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public function __construct(Connection $connection){
    $this->database = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
  * Function to get registered users 
  */
  public function queryClassSchedule($student_id = FALSE){
    if($student_id != 'create'){
      $connection = $this->database;
      $query = $connection->select('course_sign_up', 'csu')
      ->fields('csu')
      ->fields('s', ['student_id','username', 'email'])
      ->condition('csu.student_id', $student_id);
      // Query join
      $query->join('students', 's', 'csu.student_id = s.student_id');
      $results = $query->execute()->fetchAll();
      // Get form fields
      $data = [];
      foreach($results as $result){
        // User info
        $data['student_id'] = $result->student_id;
        $data['username'] = $result->username;
        $data['email'] = $result->email;
        // Class info
        $data['register_id'][] = $result->register_id;
        $data['class_subject'][] = $result->class_subject;
        $data['class_topic'][] = $result->class_topic;
        $data['class_timeslot'][] =  $result->class_timeslot;
      }
      return $data;
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function content($student_id){
    $query_class_schedule = $this->queryClassSchedule($student_id);
    $form = $this->formBuilder()->getForm('Drupal\course_sign_up\Form\CourseSignUp');
    return [
      '#theme' => 'course_sign_up_page',
      '#signup_form' => $form,
      '#class_schedule' => $query_class_schedule, 
      '#attached' =>[
        'library' => [
          'course_sign_up/drupal.custom-libraries'
        ],
      ],
    ];
  } 
}
 