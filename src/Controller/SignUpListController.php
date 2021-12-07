<?php
namespace Drupal\course_sign_up\Controller;
 
use Drupal\node\Entity\Node;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
 
class SignUpListController extends ControllerBase {

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
  * Function to get course signup list 
  */
  public function querySignUpList(){
    $connection = $this->database;
    $query = $connection->select('students', 's')
    ->fields('s');
    $results = $query->execute()->fetchAll();

    return $results;
  }
  
  /**
   * {@inheritdoc}
   */
  public function content(){
    $sign_up_list = $this->querySignUpList();
    return [
      '#theme' => 'course_sign_up_list',
      '#signup_list' => $sign_up_list,
      '#attached' =>[
        'library' => [
          'course_sign_up/drupal.custom-libraries'
        ],
      ],
    ];
  } 
}
 