<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * A pool of real computer science course titles to draw from.
     *
     * @var array<int, string>
     */
    private const array COURSE_TITLES = [
        'Introduction to Computer Science',
        'Fundamentals of Programming',
        'Data Structures and Algorithms',
        'Discrete Mathematics for Computer Science',
        'Object-Oriented Programming',
        'Computer Organization and Architecture',
        'Operating Systems',
        'Database Systems',
        'Computer Networks',
        'Software Engineering',
        'Theory of Computation',
        'Design and Analysis of Algorithms',
        'Programming Languages and Paradigms',
        'Compilers and Language Design',
        'Artificial Intelligence',
        'Machine Learning',
        'Deep Learning',
        'Natural Language Processing',
        'Computer Vision',
        'Reinforcement Learning',
        'Human-Computer Interaction',
        'Web Application Development',
        'Mobile Application Development',
        'Cloud Computing',
        'Distributed Systems',
        'Parallel and Concurrent Programming',
        'Cybersecurity Fundamentals',
        'Cryptography and Network Security',
        'Ethical Hacking and Penetration Testing',
        'Digital Forensics',
        'Computer Graphics',
        'Game Development',
        'Virtual and Augmented Reality',
        'Embedded Systems Programming',
        'Internet of Things',
        'Robotics and Automation',
        'Digital Logic Design',
        'Microprocessors and Assembly Language',
        'Data Mining',
        'Big Data Analytics',
        'Data Visualization',
        'Information Retrieval',
        'Bioinformatics',
        'Quantum Computing',
        'Functional Programming',
        'Logic Programming',
        'Formal Methods and Verification',
        'Automata Theory and Formal Languages',
        'Computational Complexity',
        'Numerical Methods',
        'Linear Algebra for Computer Science',
        'Probability and Statistics for Computing',
        'Graph Theory',
        'Combinatorics and Algorithms',
        'Cryptographic Protocols',
        'Blockchain and Distributed Ledgers',
        'Software Testing and Quality Assurance',
        'Software Architecture and Design Patterns',
        'Agile Software Development',
        'DevOps and Continuous Delivery',
        'Version Control and Collaboration',
        'Full-Stack Web Development',
        'Front-End Engineering',
        'Back-End Engineering',
        'API Design and Development',
        'Microservices Architecture',
        'Containerization and Orchestration',
        'Site Reliability Engineering',
        'Systems Programming in C',
        'Modern C++ Programming',
        'Python for Data Science',
        'Java Enterprise Development',
        'Scripting with Python',
        'Rust Systems Programming',
        'Go for Backend Services',
        'JavaScript and TypeScript Fundamentals',
        'Computer Systems and Low-Level Programming',
        'Advanced Operating Systems',
        'Advanced Database Systems',
        'Advanced Computer Networks',
        'Network Programming',
        'Wireless and Mobile Networks',
        'Real-Time Systems',
        'High-Performance Computing',
        'GPU Programming and CUDA',
        'Computer Architecture and Performance',
        'Compiler Optimization',
        'Operating System Kernel Development',
        'Ethics in Computing',
        'Professional Practice in Software Engineering',
        'Introduction to Data Science',
        'Statistical Learning',
        'Neural Networks and Deep Architectures',
        'Speech Recognition and Synthesis',
        'Recommender Systems',
        'Knowledge Representation and Reasoning',
        'Multi-Agent Systems',
        'Evolutionary Computation',
        'Fuzzy Logic and Soft Computing',
        'Computational Geometry',
        'Cryptocurrency and Smart Contracts',
        'Secure Software Development',
        'Malware Analysis and Reverse Engineering',
        'Network Defense and Monitoring',
        'Cloud Security and Compliance',
        'Data Warehousing and ETL',
        'NoSQL and Distributed Databases',
        'Stream Processing and Event-Driven Systems',
        'Interaction Design and Usability',
        'Accessibility in Software Design',
        'Digital Signal Processing',
        'Image Processing',
        'Pattern Recognition',
        'Sensor Networks and Edge Computing',
        'Autonomous Systems and Control',
        'Computational Neuroscience',
        'Introduction to Artificial Neural Networks',
        'Foundations of Cybersecurity Policy',
        'Software Project Management',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => fake()->randomElement(CourseStatus::cases()),
            'title' => fake()->unique()->randomElement(self::COURSE_TITLES),
            'code' => strtoupper(fake()->unique()->bothify('???-###')),
            'description' => '<p>'.fake()->paragraph().'</p>',
            'created_by_id' => 1,
        ];
    }

    /**
     * Indicate that the course is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::Published,
        ]);
    }

    /**
     * Attach a generated cover image to the course.
     */
    public function withCover(): static
    {
        return $this->afterCreating(function (Course $course) {
            $course->addMedia(UploadedFile::fake()->image('cover.jpg', 800, 450))
                ->toMediaCollection('cover');
        });
    }
}
