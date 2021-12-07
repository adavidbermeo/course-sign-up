<?php
namespace Drupal\course_sign_up\Form;
 
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
 
 
class CourseSignUp extends FormBase {

    /**
     * Database connection
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $database;

    /**
     * Provides messenger service.
     *
     * @var Drupal\Core\Routing\RouteMatchInterface;
    */
    protected $messenger;

    /**
     * @var Drupal\Core\Routing\RouteMatch;
    */
    protected $route_match;

    /**
     * Student ID.
     *
    */
    protected $student_id;

    public function __construct(Connection $connection, MessengerInterface $messenger, RouteMatchInterface $route_match){
        $this->database = $connection;
        $this->messenger = $messenger;
        $this->route_match = $route_match;
    }

    /**
     * {@inheritdoc}
    */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('database'),
            $container->get('messenger'),
            $container->get('current_route_match')
        );
    }
 
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'course_sign_up_form';
    }

    /**
     * Function to get the default values of the user information
     */
    public function getDefaultValue($field, $student_id = FALSE){
        $results = '';
        if($student_id != 'create'){
            $query = $this->database->select('course_sign_up', 'csu')
            ->fields('s', ['username', 'email'])
            ->condition('s.student_id', $student_id);
            $query->join('students', 's', 'csu.student_id = s.student_id');

            $results = $query->execute()->fetchAssoc();
        }
        // Set default value accordding to field required
        if(is_array($results)){
            switch($field){
                case 'username':
                        $default_value = $results['username'];
                    break;
                case 'email':
                        $default_value = $results['email'];
                    break;
            }
        } else{
            $default_value = '';
        }
        return $default_value;
    }
 
    /**
     * All form fields are defined 
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $this->student_id = $this->route_match->getParameter('student_id');

        $form['container'] = [
            '#prefix' => '<div id="my_form_wrapper" class="messages__wrapper layout-container">',
            '#suffix' => '</div>'
        ];
        // Username Field
        $form['container']['username'] = [
            '#type'     => 'textfield',
            '#title'    => $this->t('Username'),
            '#required' => TRUE,
            '#attributes' => [
                'placeholder' => 'Write your username here'
            ],
            '#default_value' => $this->getDefaultValue('username', $this->student_id),
            '#disabled' => $this->getDefaultValue('username', $this->student_id) ? TRUE : FALSE
        ];
        // Email Field
        $form['container']['email'] = array(
            '#type' => 'email',
            '#title' => t('Email'),
            '#required' => TRUE,
            '#description' => "Please enter your email.",
            '#default_value' => $this->getDefaultValue('email', $this->student_id),
            '#disabled' => $this->getDefaultValue('email', $this->student_id) ? TRUE : FALSE
          );
        // Custom class Subjects
        $class_subjects = [ 
            'math' => 'Math',
            'science' => 'Science', 
            'art' => 'Art',
            'language_arts' => 'Language Arts'
        ];
        // Subjects Field
        $form['container']['subjects'] = [
            '#type'     => 'select',
            '#title'    => $this->t('Class subjects'),
            '#options' =>  $class_subjects,
            '#description' => 'Please, choose a class subject from the list to be added',
            '#required' => TRUE,
            '#ajax' => [
                'wrapper' => 'topics-container',
                'callback' => '::loadTopics',
                'event' => 'change',
            ]
        ];
        // Topics Field
        $form['container']['topics'] = [
            '#type'     => 'select',
            '#title'    => $this->t('Class Topics'),
            '#options' =>  $this->classTopics($form_state->getValue('subjects')),
            '#description' => 'Please, choose a class subject from the list to be added',
            '#required' => TRUE,
            '#prefix' => '<div id="topics-container">',
            '#suffix' => '</div>',
            '#ajax' => [
                'wrapper' => 'timeslot-container',
                'callback' => '::loadTimeslot',
                'event' => 'change',
            ]
        ];
        // Timeslot Field
        $form['container']['timeslot'] = [
            '#type'     => 'select',
            '#title'    => $this->t('Class Timeslot'),
            '#options' =>  $this->classTimeslot($form_state->getValue('topics')),
            '#description' => 'Please, choose a time slot for the class',
            '#required' => TRUE,
            '#prefix' => '<div id="timeslot-container">',
            '#suffix' => '</div>',
        ];
        // Add class button
        $form['container']['submit'] = [
            '#type'  => 'submit',
            '#value' => $this->t('Add Class'),
            '#prefix' => '<div class="add-class-button">',
            '#suffix' => '</div>'
        ];

        return $form;
    }

    /**
     * Refresh ajax form to load Topics select options
     */
    public function loadTopics(array &$form, FormStateInterface $form_state) {
        return $form['container']['topics'];
    }

     /**
     * Refresh ajax form to load Time slot select options
     */
    public function loadTimeslot(array &$form, FormStateInterface $form_state) {
        return $form['container']['timeslot'];
    }

    /**
     * Topics selector structure
     */
    public function classTopics($csubject_id){
        switch($csubject_id){
            case 'math':
                $class_topics = [
                    'algebra' => 'Algebra',
                    'trigonometry' => 'Trigonometry',
                    'calculus' => 'Calculus'
                ];
                break;
            case 'science':
                $class_topics = [
                    'physics' => 'Physics',
                    'chemistry' => 'Chemistry',
                    'biology' => 'Biology'
                ];
                break;
            case 'art':
                $class_topics = [
                    'art_history' => 'Art History',
                    'painting' => 'Painting',
                    'drawing' => 'Drawing'
                ];
                break;
            case 'language_arts':
                $class_topics = [
                    'literature' => 'Literature',
                    'grammar' => 'Grammar',
                    'writing' => 'Writing'
                ];
                break;
            default:
                $class_topics = [];
        }

        return $class_topics;
    }

    /**
     * Timeslot selector structure
     */
    public function classTimeslot($ctopic_id){
        switch($ctopic_id){
            case 'algebra':
                $class_timeslot = [
                    '8_AM' => '8:00 AM',
                    '11_AM' => '11:00 AM'
                ];
                break;
            case 'trigonometry':
                $class_timeslot = [
                    '9_AM' => '9:00 AM',
                    '12_PM' => '12:00 PM'
                ];
                break;
            case 'calculus':
                $class_timeslot = [
                    '10_AM' => '10:00 AM',
                    '3_PM' => '3:00 PM'
                ];
                break;
            case 'physics':
                $class_timeslot = [
                    '10_AM' => '10:00 AM',
                    '3_PM' => '3:00 PM'
                ];
                break;
            case 'chemistry':
                $class_timeslot = [
                    '9_AM' => '9:00 AM',
                    '1_PM' => '1:00 PM'
                ];
                break;
            case 'biology':
                $class_timeslot = [
                    '8_AM' => '8:00 AM',
                    '10_AM' => '10:00 AM'
                ];
                break;
            case 'art_history':
                $class_timeslot = [
                    '11_AM' => '11:00 AM'
                ];
                break;
            case 'painting':
                $class_timeslot = [
                    '2_PM' => '2:00 PM'
                ];
                break;
            case 'drawing':
                $class_timeslot = [
                    '8_AM' => '8:00 AM',
                    '5_AM' => '5:00 AM'
                ];
                break;
            case 'literature':
                $class_timeslot = [
                    '8_30_AM' => '8:30 AM',
                    '11_45_AM' => '11:45 AM'
                ];
                break;
            case 'grammar':
                $class_timeslot = [
                    '8_AM' => '8:00 AM',
                    '9_AM' => '9:00 AM',
                    '10_AM' => '10:00 AM',
                    '11_AM' => '11:00 AM',
                    '1_PM' => '1:00 PM'
                ];
                break;
            case 'writing':
                $class_timeslot = [
                    '8_AM' => '8:00 AM',
                    '11_AM' => '11:00 AM'
                ];
                break;    
            default:
                $class_timeslot = [];
        }
        return $class_timeslot;
    }
 
    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        if (!ctype_alnum(str_replace(' ','', $form_state->getValue('username')))) {
            $form_state->setErrorByName('username', $this->t('Only alphanumeric characters are accepted'));
        }
    }
 
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
       //Database Connection
        $connection = $this->database;
        $username = $form_state->getValue('username');
        $email = $form_state->getValue('email');
        // Form field values
        $class_subject_key = $form_state->getValue('subjects');
        $class_subject = $form['container']['subjects']['#options'][$class_subject_key];
        $class_topic_key = $form_state->getValue('topics');
        $class_topic = $form['container']['topics']['#options'][$class_topic_key];
        $class_timeslot_key = $form_state->getValue('timeslot');
        $class_timeslot = $form['container']['timeslot']['#options'][$class_timeslot_key];
        // Create new student
        if($this->student_id == 'create'){
            $result_student = $connection->insert('students')
            ->fields([
                'student_id' => null,
                'username' => $username,
                'email' => $email,
            ])
            ->execute();
            // Check id of new student generated
            $query = $connection->select('students', 's')
            ->fields('s', ['student_id'])
            ->condition('username', $username)
            ->condition('email', $email);

            $results = $query->execute()->fetchAssoc();
            $student_id = $results['student_id'];
        }
        // Insert class values
        $result_sign_up = $connection->insert('course_sign_up')
        ->fields([
            'register_id' => null,
            'student_id' => ($this->student_id != 'create' ) ? $this->student_id : $student_id,
            'class_subject' => $class_subject,
            'class_topic' => $class_topic,
            'class_timeslot' => $class_timeslot
        ])
        ->execute();
        // The user is redirected with a confirmation message according to the final result of the process
        if(!empty($result_sign_up)){
            $student_redirect = ($this->student_id != 'create') ? $this->student_id : $student_id;
            $response = new RedirectResponse('/sign-up/page/'. $student_redirect);
            $response->send();
            $this->messenger->addMessage($this->t('Data has been saved successfully'));
        }else{
            $response = new RedirectResponse('/sign-up/page/create');
            $response->send();
            $this->messenger->addMessage($this->t('The data have not been saved. Please try again'));
        }
    }
}
