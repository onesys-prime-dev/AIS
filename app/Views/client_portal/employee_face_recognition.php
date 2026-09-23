<div class="flex flex-col w-full min-w-0 min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex justify-between items-center my-4">

        <div class="p-1">
            <h1 class="text-3xl font-bold text-gray-800 mb-1">
                Face Recognition
            </h1>

            <p class="text-gray-500 text-sm">
                Enroll or manage each employee's face for login verification.
            </p>
        </div>
    </div>

    <?= view('components/crud_table', [
        'title' => 'Employee Face Enrollment',
        'tableId' => 'faceRecognitionTable',
        'grid' => 'grid-cols-[2fr_1fr_1.1fr_1fr_1.1fr_1.3fr_0.8fr]',
        'columns' => [
            'Employee Name',
            'Position',
            'Department',
            'ID',
            'Face Status',
            'Login Verification',
            'Action'
        ],
        'filters' => [
            [
                'key' => 'face_status',
                'label' => 'Face Status',
                'options' => [
                    ['value' => '', 'label' => 'All'],
                    ['value' => 'registered', 'label' => 'Registered'],
                    ['value' => 'not_registered', 'label' => 'Not Registered'],
                ],
            ],
            [
                'key' => 'department',
                'label' => 'Department',
                'autofill' => true,
            ],
        ],
        'sortOptions' => [
            ['value' => 'name', 'label' => 'Employee Name'],
            ['value' => 'department', 'label' => 'Department'],
            ['value' => 'enrolled', 'label' => 'Face Status'],
        ],
    ]) ?>

</div>
