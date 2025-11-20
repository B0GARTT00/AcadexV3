<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Course;

class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        // Get the courses
        $bsit = Course::where('course_code', 'BSIT')->first();
        $bsba = Course::where('course_code', 'BSBA')->first();
        $bspsy = Course::where('course_code', 'BSPSY')->first();

        if (!$bsit || !$bsba || !$bspsy) {
            throw new \Exception('Required courses not found. Seed courses first.');
        }

        // Create curriculums for each course
        $bsitCurriculum = Curriculum::firstOrCreate([
            'course_id' => $bsit->id,
            'name' => '2022 Curriculum',
        ]);

        $bsbaCurriculum = Curriculum::firstOrCreate([
            'course_id' => $bsba->id,
            'name' => '2022 Curriculum',
        ]);

        $bspsyCurriculum = Curriculum::firstOrCreate([
            'course_id' => $bspsy->id,
            'name' => '2022 Curriculum',
        ]);

        // BSIT Subjects
        $bsitSubjects = [
            [1, '1st', 'GE 1', 'Understanding the Self'],
            [1, '1st', 'GE 4', 'Mathematics in the Modern World'],
            [1, '1st', 'GE 5', 'Purposive Communication'],
            [1, '1st', 'IT 101', 'Introduction to Computing'],
            [1, '1st', 'IT 102', 'Computer Programming 1'],
            [1, '1st', 'NSTP 1', 'Civic Welfare Training Service 1 (CWTS1)'],
            [1, '1st', 'PD 1', 'Academic & Personal Adjustment'],
            [1, '1st', 'PE 1', 'Movement Enhancement'],
            [1, '1st', 'RS 1', 'Message & Teaching of Old Testament'],

            [1, '2nd', 'GE 2', 'Readings in Philippine History'],
            [1, '2nd', 'GE 7', 'Science, Technology and Society'],
            [1, '2nd', 'IT 103', 'Computer Programming 2'],
            [1, '2nd', 'IT 104', 'Introduction to Human Computer Interaction'],
            [1, '2nd', 'IT 105', 'Discrete Mathematics'],
            [1, '2nd', 'NSTP 2', 'Civic Welfare Training Service 2 (CWTS 2)'],
            [1, '2nd', 'PD 2', 'Self-Awareness and Self-Management'],
            [1, '2nd', 'PE 2', 'Fitness Exercises'],
            [1, '2nd', 'RS 2', 'Message and Teachings of New Testament'],

            [2, '1st', 'GE 11', 'Living in the IT Era'],
            [2, '1st', 'GE 9', 'Life and Works of Rizal'],
            [2, '1st', 'IT 1', 'IT Elective 1'],
            [2, '1st', 'IT 201', 'Data Structure and Algorithm'],
            [2, '1st', 'IT 202', 'Computer Organization & Architecture'],
            [2, '1st', 'IT 203', 'Accounting for IT'],
            [2, '1st', 'PD 3', 'Values Development & Interpersonal Relationship'],
            [2, '1st', 'PE 3', 'Dance for Theatre'],

            [2, '2nd', 'GE 12', "People and Earth's Ecosystem"],
            [2, '2nd', 'GE 3', 'The Contemporary World'],
            [2, '2nd', 'IT 2', 'IT Elective 2'],
            [2, '2nd', 'IT 204', 'Information Management'],
            [2, '2nd', 'IT 205', 'Integrative Programming and Technologies 1'],
            [2, '2nd', 'IT 206', 'Networking 1'],
            [2, '2nd', 'PD 4', 'Career Development & Community Involvement'],
            [2, '2nd', 'PE 4', 'Sports Activities (Individual, Dual and Team Sports)'],

            [3, '1st', 'GE 6', 'Art Appreciation'],
            [3, '1st', 'IT 3', 'IT Elective 3'],
            [3, '1st', 'IT 301', 'Advanced Database System'],
            [3, '1st', 'IT 302', 'System Integration and Architecture 1'],
            [3, '1st', 'IT 303', 'Networking 2'],
            [3, '1st', 'IT 304', 'Quantitative Methods (w/Modeling and Simulation)'],
            [3, '1st', 'IT 305', 'Social and Professional Issues in IT'],

            [3, '2nd', 'GE 10', 'The Entrepreneurial Mind'],
            [3, '2nd', 'GE 8', 'Ethics'],
            [3, '2nd', 'IT 306', 'Applications Development & Emerging Tech'],
            [3, '2nd', 'IT 307', 'Information and Assurance and Security 1'],
            [3, '2nd', 'IT 308', 'Web Frameworks'],
            [3, '2nd', 'IT 309', 'Project Management'],
            [3, '2nd', 'IT 4', 'IT Elective 4'],

            [4, '1st', 'IT 400', 'Practicum (486 hours)'],
            [4, '1st', 'IT 401', 'Capstone Project 1'],
            [4, '1st', 'IT 402', 'Mobile Application Development'],
            [4, '1st', 'IT 403', 'Information Assurance and Security 2'],

            [4, '2nd', 'IT 404', 'Capstone Project 2'],
            [4, '2nd', 'IT 405', 'Systems Administration and Maintenance'],
            [4, '2nd', 'IT 406', 'Emerging Technologies in IT'],
        ];

        // BSBA Subjects
        $bsbaSubjects = [
            [1, '1st', 'BA CC 1', 'Basic Microeconomics'],
            [1, '1st', 'BA MKTG 1', 'Basic Microeconomics'],
            [1, '1st', 'BA MKTG 101', 'Accounting for Non-Accountants'],
            [1, '1st', 'GE 2', 'Readings in Philippine History'],
            [1, '1st', 'GE 7', 'Science, Teaching and Society'],
            [1, '1st', 'NSTP 1', 'Civic Welfare Training Service 1 (CWTS1)'],
            [1, '1st', 'PD 1', 'Academic & Personal Adjustment'],
            [1, '1st', 'PE 1', 'Movement Enhancement'],
            [1, '1st', 'RS 1', 'Message & Teaching of Old Testament'],

            [1, '2nd', 'BA MKTG 2', 'BA MKTG 2- Price Strategy'],
            [1, '2nd', 'BA MKTG 3', 'Professional Salesmanship'],
            [1, '2nd', 'GE 1', 'Understanding the Self'],
            [1, '2nd', 'GE 5', 'Purposive Communication'],
            [1, '2nd', 'NSTP 2', 'Civic Welfare Training Service 2 (CWTS 2)'],
            [1, '2nd', 'PD 2', 'Self-Awareness and Self-Management'],
            [1, '2nd', 'PE 2', 'Fitness Exercises'],
            [1, '2nd', 'RS 2', 'Message and Teachings of New Testament'],

            [2, '1st', 'BA CC 2', 'Human Resource Management'],
            [2, '1st', 'BA MKTG 4', 'Advertising'],
            [2, '1st', 'BA MKTG ELEC 1', 'E-Commerce and Internet Marketing'],
            [2, '1st', 'BA MKTG ELEC 2', 'Consumer Behavior'],
            [2, '1st', 'GE 11', 'LIVING IN THE IT ERA'],
            [2, '1st', 'GE 3', 'The Contemporary World'],
            [2, '1st', 'PD 3', 'Values Development & Interpersonal Relationship'],
            [2, '1st', 'PE 3', 'Dance for Theatre'],

            [2, '2nd', 'BA CC 3', "Income Taxation"],
            [2, '2nd', 'BA CC 4', 'Obligation and Contracts'],
            [2, '2nd', 'BA MKTG 5', 'Distribution Management'],
            [2, '2nd', 'BA MKTG 6', 'Retail Management'],
            [2, '2nd', 'GE 4', 'Mathematics in the Modern World'],
            [2, '2nd', 'GE 9', 'Rizals Life and Works'],
            [2, '2nd', 'PD 4', 'Career Development & Community Involvement'],
            [2, '2nd', 'PE 4', 'Sports Activities (Individual, Dual and Team Sports)'],

            [3, '1st', 'BA CBMEC 1', 'Operations Management (TQM)'],
            [3, '1st', 'BA CC 5', 'Business Research'],
            [3, '1st', 'BA CC 6', 'Good Governance and Social Responsibility'],
            [3, '1st', 'BA MKTG 102', 'Services Marketing'],
            [3, '1st', 'BA MKTG 7', 'Marketing Management'],
            [3, '1st', 'BA MKTG ELEC 3', 'Sales Management'],
            [3, '1st', 'GE 8', 'Ethics'],

            [3, '2nd', 'BA MKTG 103', 'Industrial/Agricultural Marketing'],
            [3, '2nd', 'BA MKTG 8', 'Marketing Research'],
            [3, '2nd', 'BA MKTG ELEC 4', 'Franchising'],
            [3, '2nd', 'GE 10', 'The Entrepreneurial Mind'],
            [3, '2nd', 'GE 12', 'PEOPLE AND THE EARTHS ECOSYSTEM'],
            [3, '2nd', 'GE 6', 'Art Appreciation'],

            [4, '1st', 'BA CBMEC 2', 'BA CBMEC 2- Strategic Management'],
            [4, '1st', 'BA CC 7', 'International Business Trade & Agreements '],
            [4, '1st', 'BA CC 8', 'Feasibility Study'],
            [4, '1st', 'BA MKTG 104', 'Entrepreneurial Management'],
            [4, '1st', 'BA MKTG 105', 'Strategic Marketing Management'],

            [4, '2nd', 'PRACTICUM', 'Practicum/ Work Integrated Learning (600 hours)'],
        ];

        // BSPSY Subjects
        $bspsySubjects = [
            [1, '1st', 'GE 1', 'UNDERSTANDING THE SELF'],
            [1, '1st', 'GE 5', 'PURPOSIVE COMMUNICATION'],
            [1, '1st', 'NS 1', 'GENERAL INORGANIC CHEMISTRY'],
            [1, '1st', 'NSTP 1', 'NSTP1- CIVIC WELFARE TRAINING SERVICE 1 (CWTS 1)'],
            [1, '1st', 'PD 1', 'ACADEMIC AND PERSONAL DEVELOPMENT'],
            [1, '1st', 'PE 1', 'MOVEMENT ENHANCEMENT'],
            [1, '1st', 'PSYCH 101', 'INTRODUCTION TO PSYCHOLOGY'],
            [1, '1st', 'RS 1', 'Message & Teaching of Old Testament'],

            [1, '2nd', 'GE 2', 'READINGS IN PHILIPPINE HISTORY'],
            [1, '2nd', 'GE 7', 'SCIENCE, TECHNOLOGY AND SOCIETY'],
            [1, '2nd', 'NSTP 2', 'CIVIC WELFARE TRAINING SERVICE (CWTS2)'],
            [1, '2nd', 'PD 2', 'SELF-AWARENESS AND SELF-MANAGEMENT'],
            [1, '2nd', 'PE 2', 'Fitness Exercises'],
            [1, '2nd', 'PSYCH 102', 'PSYCHOLOGICAL STATISTICS'],
            [1, '2nd', 'RS 2', 'Message and Teachings of New Testament'],

            [2, '1st', 'GE 4', 'MATHEMATICS IN THE MODERN WORLD'],
            [2, '1st', 'GE 9', 'LIFE AND WORKS OF RIZAL'],
            [2, '1st', 'NS ELEC 2', 'ZOOLOGY'],
            [2, '1st', 'PD 3', 'VALUES DEVELOPMENT & INTERPERSONAL RELATIONSHIP'],
            [2, '1st', 'PE 3', 'DANCE FOR THEATER'],
            [2, '1st', 'PSYCH 103', 'DEVELOPMENTAL PSYCHOLOGY'],
            [2, '1st', 'PSYCH 105', 'PSYCHOLOGICAL / BIOLOGICAL PSYCHOLOGY'],

            [2, '2nd', 'GE 10', "THE ENTREPRENEURIAL MIND"],
            [2, '2nd', 'GE 3', 'THE CONTEMPORARY WORLD '],
            [2, '2nd', 'NS ELEC 3', 'HUMAN ANATOMY & PHYSIOLOGY'],
            [2, '2nd', 'PD 4', 'CAREER DEVELOPMENT & COMMUNITY INVOLVEMENT'],
            [2, '2nd', 'PE 4', 'SPORTS ACTIVITIES [INDIVIDUAL, DUAL AND TEAM SPORTS]'],
            [2, '2nd', 'PSYCH 104', 'THEORIES OF PERSONALITY'],
            [2, '2nd', 'PSYCH 106', 'COGNITIVE PSYCHOLOGY'],
            [2, '2nd', 'PSYCH 107', 'EXPERIMENTAL PSYCHOLOGY'],

            [3, '1st', 'GE  11', 'LIVING IN THE IT ERA'],
            [3, '1st', 'GE 6', 'ART APPRECIATION'],
            [3, '1st', 'NS ELEC 4', ' GENERAL PHYSICS'],
            [3, '1st', 'PSYCH 108', 'FIELD METHODS IN PSYCHOLOGY'],
            [3, '1st', 'PSYCH 109', 'ABNORMAL PSYCHOLOGY'],
            [3, '1st', 'PSYCH 110', 'SOCIAL PSYCHOLOGY'],
            [3, '1st', 'PSYCH 114', 'INTRODUCTION TO CONSULTING (ELECTIVE)'],

            [3, '2nd', 'GE 12', 'PEOPLE AND THE EARTHS ECOSYSTEM GE8- ETHICS'],
            [3, '2nd', 'PSYCH 111', 'PSYCHOLOGICAL ASSESMENT'],
            [3, '2nd', 'PSYCH 112', 'INDUSTRIAL/ORGANIZATIONAL PSYCHOLOGY'],
            [3, '2nd', 'PSYCH 113', 'SIKOLOHIYANG PILIPINO'],
            [3, '2nd', 'PSYCH 117', 'EDUCATIONAL PSYCHOLOGY (ELECTIVE)'],
            [3, '2nd', 'PSYRES 1', 'RESEAECH IN PSYCHOLOGY 1'],

            [4, '1st', 'PSYCH 115', 'DISASTER AND MENTAL HEALTH (ELECTIVE)'],
            [4, '1st', 'PSYCH 118', 'GROUP DYNAMICS (ELECTIVE)'],
            [4, '1st', 'PSYRES 2', 'RESEARCH IN PSYCHOLOGY 2'],

            [4, '2nd', 'PSYCH 116', 'INTRODUCTIVE TO CLINICAL PSYCHOLOGY (ELECTIVE)'],
            [4, '2nd', 'PSYCH 119', 'PRACTICUM (ELECTIVE)'],
            [4, '2nd', 'PSYCH 120', 'CURRENT ISSUES IN PSYCHOLOGY'],
        ];

        // Create subjects for each curriculum
        foreach ($bsitSubjects as [$year, $sem, $code, $desc]) {
            CurriculumSubject::firstOrCreate([
                'curriculum_id' => $bsitCurriculum->id,
                'subject_code' => $code,
                'year_level' => $year,
                'semester' => $sem,
            ], [
                'subject_description' => $desc,
            ]);
        }

        foreach ($bsbaSubjects as [$year, $sem, $code, $desc]) {
            CurriculumSubject::firstOrCreate([
                'curriculum_id' => $bsbaCurriculum->id,
                'subject_code' => $code,
                'year_level' => $year,
                'semester' => $sem,
            ], [
                'subject_description' => $desc,
            ]);
        }

        foreach ($bspsySubjects as [$year, $sem, $code, $desc]) {
            CurriculumSubject::firstOrCreate([
                'curriculum_id' => $bspsyCurriculum->id,
                'subject_code' => $code,
                'year_level' => $year,
                'semester' => $sem,
            ], [
                'subject_description' => $desc,
            ]);
        }
    }
}
