<?php
namespace Akti\Services;

use Akti\Utils\Input;
use Akti\Utils\Validator;

/**
 * Service: CustomerFormService
 *
 * Encapsula captura, sanitização e validação dos dados do formulário de cliente.
 * Extraído do CustomerController para manter o controller enxuto.
 *
 * @package Akti\Services
 */
class CustomerFormService
{
    /**
     * Captura e sanitiza todos os campos do formulário de cliente.
     *
     * @return array Dados sanitizados
     */
    public function captureFormData(): array
    {
        // Campos básicos de identificação
        $personType  = Input::post('person_type', 'string', 'PF');
        $name        = Input::post('name');
        $fantasyName = Input::post('fantasy_name');
        $document    = Input::post('document');
        $rgIe        = Input::post('rg_ie');
        $im          = Input::post('im');
        $birthDate   = Input::post('birth_date');
        $gender      = Input::post('gender');

        // Converter data de nascimento de DD/MM/AAAA para Y-m-d
        if ($birthDate) {
            $birthDate = trim($birthDate);
            if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $birthDate, $m)) {
                $birthDate = $m[3] . '-' . $m[2] . '-' . $m[1];
            } elseif (preg_match('#^(\d{2})-(\d{2})-(\d{4})$#', $birthDate, $m)) {
                $birthDate = $m[3] . '-' . $m[2] . '-' . $m[1];
            }
        }

        // Campos de contato
        $email          = Input::post('email', 'email');
        $emailSecondary = Input::post('email_secondary', 'email');
        $phone          = Input::post('phone', 'phone');
        $cellphone      = Input::post('cellphone', 'phone');
        $phoneComm      = Input::post('phone_commercial', 'phone');
        $website        = Input::post('website');
        $instagram      = Input::post('instagram');
        $contactName    = Input::post('contact_name');
        $contactRole    = Input::post('contact_role');

        // Campos de endereço
        $zipcode       = Input::post('zipcode');
        $street        = Input::post('address_street');
        $number        = Input::post('address_number');
        $complement    = Input::post('address_complement');
        $neighborhood  = Input::post('address_neighborhood');
        $city          = Input::post('address_city');
        $state         = Input::post('address_state');
        $country       = Input::post('address_country', 'string', 'Brasil');
        $ibge          = Input::post('address_ibge');

        // Campos comerciais
        $priceTableId    = Input::post('price_table_id', 'int');
        $paymentTerm     = Input::post('payment_term');
        $creditLimit     = Input::post('credit_limit');
        $discountDefault = Input::post('discount_default');
        $sellerId        = Input::post('seller_id', 'int');
        $origin          = Input::post('origin');
        $tags            = Input::post('tags');
        $observations    = Input::post('observations');
        $status          = Input::post('status', 'string', 'active');

        // Sanitizações específicas
        $name       = trim(preg_replace('/\s+/', ' ', $name ?? ''));
        $document   = preg_replace('/\D/', '', $document ?? '');
        $phone      = preg_replace('/\D/', '', $phone ?? '');
        $cellphone  = preg_replace('/\D/', '', $cellphone ?? '');
        $phoneComm  = preg_replace('/\D/', '', $phoneComm ?? '');
        $zipcode    = preg_replace('/\D/', '', $zipcode ?? '');
        $email      = $email ? trim(strtolower($email)) : null;
        $emailSecondary = $emailSecondary ? trim(strtolower($emailSecondary)) : null;

        // Instagram: remover @ inicial
        if ($instagram && strpos($instagram, '@') === 0) {
            $instagram = substr($instagram, 1);
        }

        // Website: adicionar https:// se não tiver protocolo
        if ($website && !preg_match('#^https?://#i', $website)) {
            $website = 'https://' . $website;
        }

        // Credit limit: converter para float (aceitar formato BR)
        if ($creditLimit !== null && $creditLimit !== '') {
            $creditLimit = str_replace(['R$', ' ', '.'], '', $creditLimit);
            $creditLimit = str_replace(',', '.', $creditLimit);
            $creditLimit = is_numeric($creditLimit) ? (float) $creditLimit : null;
        }

        // Discount: converter para float
        if ($discountDefault !== null && $discountDefault !== '') {
            $discountDefault = str_replace(['%', ' '], '', $discountDefault);
            $discountDefault = str_replace(',', '.', $discountDefault);
            $discountDefault = is_numeric($discountDefault) ? (float) $discountDefault : null;
        }

        return [
            'person_type'          => $personType,
            'name'                 => $name,
            'fantasy_name'         => $fantasyName ?: null,
            'document'             => $document ?: null,
            'rg_ie'                => $rgIe ?: null,
            'im'                   => $im ?: null,
            'birth_date'           => $birthDate ?: null,
            'gender'               => $gender ?: null,
            'email'                => $email ?: null,
            'email_secondary'      => $emailSecondary ?: null,
            'phone'                => $phone ?: null,
            'cellphone'            => $cellphone ?: null,
            'phone_commercial'     => $phoneComm ?: null,
            'website'              => $website ?: null,
            'instagram'            => $instagram ?: null,
            'contact_name'         => $contactName ?: null,
            'contact_role'         => $contactRole ?: null,
            'zipcode'              => $zipcode ?: null,
            'address_street'       => $street ?: null,
            'address_number'       => $number ?: null,
            'address_complement'   => $complement ?: null,
            'address_neighborhood' => $neighborhood ?: null,
            'address_city'         => $city ?: null,
            'address_state'        => $state ?: null,
            'address_country'      => $country ?: 'Brasil',
            'address_ibge'         => $ibge ?: null,
            'price_table_id'       => $priceTableId ?: null,
            'payment_term'         => $paymentTerm ?: null,
            'credit_limit'         => $creditLimit,
            'discount_default'     => $discountDefault,
            'seller_id'            => $sellerId ?: null,
            'origin'               => $origin ?: null,
            'tags'                 => $tags ?: null,
            'observations'         => $observations ?: null,
            'status'               => $status ?: 'active',
        ];
    }

    /**
     * Validação server-side completa dos dados do cliente.
     *
     * @param array    $data      Dados sanitizados
     * @param int|null $excludeId ID a excluir na validação de unicidade (edição)
     * @return Validator
     */
    public function validateCustomerData(array $data, ?int $excludeId = null): Validator
    {
        $v = new Validator();

        // Obrigatórios
        $v->required('person_type', $data['person_type'], 'Tipo de Pessoa')
          ->inList('person_type', $data['person_type'], ['PF', 'PJ'], 'Tipo de Pessoa')
          ->required('name', $data['name'], 'Nome / Razão Social')
          ->minLength('name', $data['name'], 3, 'Nome / Razão Social')
          ->maxLength('name', $data['name'], 191, 'Nome / Razão Social');

        // Documento (CPF/CNPJ)
        if (!empty($data['document'])) {
            $v->document('document', $data['document'], $data['person_type'] ?? 'PF', 'CPF/CNPJ');
        }

        $v->maxLength('fantasy_name', $data['fantasy_name'], 191, 'Nome Fantasia');

        $v->maxLength('rg_ie', $data['rg_ie'], 30, 'RG / Inscrição Estadual')
          ->maxLength('im', $data['im'], 30, 'Inscrição Municipal');

        if (!empty($data['birth_date'])) {
            $v->date('birth_date', $data['birth_date'], 'Data de Nascimento')
              ->dateNotFuture('birth_date', $data['birth_date'], 'Data de Nascimento');
        }

        if (!empty($data['gender'])) {
            $v->inList('gender', $data['gender'], ['M', 'F', 'O'], 'Gênero');
        }

        if (!empty($data['email'])) {
            $v->email('email', $data['email'], 'E-mail')
              ->maxLength('email', $data['email'], 191, 'E-mail');
        }
        if (!empty($data['email_secondary'])) {
            $v->email('email_secondary', $data['email_secondary'], 'E-mail Secundário')
              ->maxLength('email_secondary', $data['email_secondary'], 191, 'E-mail Secundário');
        }

        $v->maxLength('phone', $data['phone'], 20, 'Telefone')
          ->maxLength('cellphone', $data['cellphone'], 20, 'Celular')
          ->maxLength('phone_commercial', $data['phone_commercial'], 20, 'Telefone Comercial');

        if (!empty($data['website'])) {
            $v->url('website', $data['website'], 'Website')
              ->maxLength('website', $data['website'], 255, 'Website');
        }

        $v->maxLength('instagram', $data['instagram'], 50, 'Instagram');

        $v->maxLength('contact_name', $data['contact_name'], 100, 'Nome do Contato')
          ->maxLength('contact_role', $data['contact_role'], 80, 'Cargo do Contato');

        $v->maxLength('address_street', $data['address_street'], 200, 'Logradouro')
          ->maxLength('address_number', $data['address_number'], 20, 'Número')
          ->maxLength('address_complement', $data['address_complement'], 100, 'Complemento')
          ->maxLength('address_neighborhood', $data['address_neighborhood'], 100, 'Bairro')
          ->maxLength('address_city', $data['address_city'], 100, 'Cidade');

        if (!empty($data['address_state'])) {
            $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
            $v->inList('address_state', strtoupper($data['address_state']), $ufs, 'UF');
        }

        $v->maxLength('payment_term', $data['payment_term'], 50, 'Condição de Pagamento');

        if ($data['credit_limit'] !== null && $data['credit_limit'] !== '') {
            $v->decimal('credit_limit', $data['credit_limit'], 'Limite de Crédito');
        }
        if ($data['discount_default'] !== null && $data['discount_default'] !== '') {
            $v->decimal('discount_default', $data['discount_default'], 'Desconto Padrão')
              ->between('discount_default', $data['discount_default'], 0, 100, 'Desconto Padrão');
        }

        $v->maxLength('origin', $data['origin'], 50, 'Origem')
          ->maxLength('tags', $data['tags'], 500, 'Tags');

        $v->inList('status', $data['status'], ['active', 'inactive', 'blocked'], 'Status');

        return $v;
    }

    /**
     * Monta o JSON de endereço para retrocompatibilidade.
     *
     * @param array $data Dados do formulário
     * @return string JSON do endereço
     */
    public function buildAddressJson(array $data): string
    {
        return json_encode([
            'zipcode'        => $data['zipcode'] ?? '',
            'address_type'   => '',
            'address_name'   => $data['address_street'] ?? '',
            'address_number' => $data['address_number'] ?? '',
            'neighborhood'   => $data['address_neighborhood'] ?? '',
            'complement'     => $data['address_complement'] ?? '',
        ]);
    }
}
