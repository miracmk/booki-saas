<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Enterprise HR Controller
 * ---------------------------------------------------------------------------- */

class Hr extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('hr_model');
        $this->load->model('users_model');
        $this->load->model('roles_model');
        $this->load->library('hr_service');
    }

    public function index(): void
    {
        $user_id = session('user_id');
        if (!$user_id) {
            redirect('login');
            return;
        }

        $tab = $this->input->get('tab') ?: 'employees';
        $search = $this->input->get('search') ?: '';
        $dept_id = (int) $this->input->get('dept_id');

        $employees = $this->hr_model->get_employees([
            'search' => $search,
            'department_id' => $dept_id,
        ]);
        $departments = $this->hr_model->get_departments();
        $designations = $this->hr_model->get_designations();
        $roles = $this->roles_model->get_all();
        $documents = $this->hr_model->get_documents();
        $assets = $this->hr_model->get_assets();
        $asset_assignments = $this->hr_model->get_asset_assignments(['active_only' => true]);
        $job_openings = $this->hr_model->get_job_openings();
        $applicants = $this->hr_model->get_job_applicants();
        $kpis = $this->hr_service->get_dashboard_kpis();
        $alerts = $this->hr_service->get_hr_alerts();
        $org_tree = $this->hr_service->get_organization_tree();

        $view = [
            'active_tab' => $tab,
            'employees' => $employees,
            'departments' => $departments,
            'designations' => $designations,
            'roles' => $roles,
            'documents' => $documents,
            'assets' => $assets,
            'asset_assignments' => $asset_assignments,
            'job_openings' => $job_openings,
            'applicants' => $applicants,
            'kpis' => $kpis,
            'alerts' => $alerts,
            'org_tree' => $org_tree,
        ];

        html_vars($view);
        $this->load->view('pages/hr');
    }

    public function employee_detail(int $user_id): void
    {
        $employee = $this->hr_model->get_employee($user_id);
        if (!$employee) {
            json_response(['success' => false, 'message' => 'Personel bulunamadı'], 404);
            return;
        }

        json_response(['success' => true, 'employee' => $employee]);
    }

    public function save_employee(): void
    {
        $data = $this->input->post();
        $user_id = (int) ($data['id_users'] ?? 0);

        if (!$user_id) {
            json_response(['success' => false, 'message' => 'Geçersiz personel ID'], 400);
            return;
        }

        $res = $this->hr_model->save_employee_profile($user_id, $data);
        json_response(['success' => $res, 'message' => 'Özlük bilgileri başarıyla kaydedildi.']);
    }

    public function save_department(): void
    {
        $data = $this->input->post();
        if (empty($data['name'])) {
            json_response(['success' => false, 'message' => 'Departman adı zorunludur.'], 400);
            return;
        }

        $id = $this->hr_model->save_department($data);
        json_response(['success' => true, 'id' => $id, 'message' => 'Departman kaydedildi.']);
    }

    public function delete_department(int $id): void
    {
        $this->hr_model->delete_department($id);
        json_response(['success' => true, 'message' => 'Departman silindi.']);
    }

    public function save_designation(): void
    {
        $data = $this->input->post();
        if (empty($data['title'])) {
            json_response(['success' => false, 'message' => 'Unvan adı zorunludur.'], 400);
            return;
        }

        $id = $this->hr_model->save_designation($data);
        json_response(['success' => true, 'id' => $id, 'message' => 'Unvan kaydedildi.']);
    }

    public function delete_designation(int $id): void
    {
        $this->hr_model->delete_designation($id);
        json_response(['success' => true, 'message' => 'Unvan silindi.']);
    }

    public function upload_document(): void
    {
        $user_id = (int) $this->input->post('id_users');
        $title = trim($this->input->post('title') ?? '');
        $doc_type = $this->input->post('document_type') ?: 'other';
        $expiry = $this->input->post('expiry_date') ?: null;

        if (!$user_id || empty($title)) {
            json_response(['success' => false, 'message' => 'Personel ve evrak başlığı zorunludur.'], 400);
            return;
        }

        $file_path = '';
        $file_name = '';

        if (!empty($_FILES['file']['name'])) {
            $upload_path = FCPATH . 'storage/hr_documents/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
            $orig_name = basename($_FILES['file']['name']);
            $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $orig_name);
            $target = $upload_path . $file_name;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
                $file_path = 'storage/hr_documents/' . $file_name;
            }
        }

        $id = $this->hr_model->save_document([
            'id_users' => $user_id,
            'title' => $title,
            'document_type' => $doc_type,
            'file_path' => $file_path ?: 'storage/hr_documents/default.pdf',
            'file_name' => $file_name ?: $title,
            'expiry_date' => $expiry,
            'status' => 'valid',
        ]);

        json_response(['success' => true, 'id' => $id, 'message' => 'Evrak başarıyla yüklendi.']);
    }

    public function delete_document(int $id): void
    {
        $this->hr_model->delete_document($id);
        json_response(['success' => true, 'message' => 'Evrak silindi.']);
    }

    public function save_asset(): void
    {
        $data = $this->input->post();
        if (empty($data['asset_name'])) {
            json_response(['success' => false, 'message' => 'Zimmet/Varlık adı zorunludur.'], 400);
            return;
        }

        $id = $this->hr_model->save_asset($data);
        json_response(['success' => true, 'id' => $id, 'message' => 'Zimmet/Varlık kaydedildi.']);
    }

    public function delete_asset(int $id): void
    {
        $this->hr_model->delete_asset($id);
        json_response(['success' => true, 'message' => 'Zimmet kaydı silindi.']);
    }

    public function assign_asset(): void
    {
        $data = $this->input->post();
        if (empty($data['id_assets']) || empty($data['id_users'])) {
            json_response(['success' => false, 'message' => 'Varlık ve personel seçimi zorunludur.'], 400);
            return;
        }

        $id = $this->hr_model->assign_asset($data);
        json_response(['success' => true, 'id' => $id, 'message' => 'Zimmet personele başarıyla teslim edildi.']);
    }

    public function return_asset(): void
    {
        $assignment_id = (int) $this->input->post('id');
        $cond = $this->input->post('condition_on_return') ?: 'Eksiksiz teslim alındı.';
        $notes = $this->input->post('notes');

        $this->hr_model->return_asset($assignment_id, $cond, $notes);
        json_response(['success' => true, 'message' => 'Zimmet iade alındı ve varlık boşa çıkarıldı.']);
    }

    public function save_job(): void
    {
        $data = $this->input->post();
        if (empty($data['title'])) {
            json_response(['success' => false, 'message' => 'İş ilanı başlığı zorunludur.'], 400);
            return;
        }

        $id = $this->hr_model->save_job_opening($data);
        json_response(['success' => true, 'id' => $id, 'message' => 'Açık pozisyon kaydedildi.']);
    }

    public function save_applicant(): void
    {
        $data = $this->input->post();
        if (empty($data['full_name']) || empty($data['id_job_openings'])) {
            json_response(['success' => false, 'message' => 'Aday adı ve pozisyon zorunludur.'], 400);
            return;
        }

        $id = $this->hr_model->save_job_applicant($data);
        json_response(['success' => true, 'id' => $id, 'message' => 'Aday başvurusu kaydedildi.']);
    }

    public function update_applicant_status(): void
    {
        $id = (int) $this->input->post('id');
        $status = $this->input->post('status') ?: 'screening';
        $notes = $this->input->post('notes');

        $this->hr_model->update_job_applicant_status($id, $status, $notes);
        json_response(['success' => true, 'message' => 'Aday durumu güncellendi.']);
    }
}
