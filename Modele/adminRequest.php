<?php

class AdminRequest
{
    private ?int $id;
    private int $sender_id;
    private string $sender_role;
    private string $receiver_scope;
    private ?int $receiver_id;
    private string $request_type;
    private ?int $target_user_id;
    private string $title;
    private string $message;
    private string $status;
    private ?string $response_message;
    private ?int $handled_by;
    private ?string $handled_by_role;
    private ?string $created_at;
    private ?string $handled_at;

    public function __construct(
        ?int $id = null,
        int $sender_id = 0,
        string $sender_role = '',
        string $receiver_scope = '',
        ?int $receiver_id = null,
        string $request_type = 'other',
        ?int $target_user_id = null,
        string $title = '',
        string $message = '',
        string $status = 'pending',
        ?string $response_message = null,
        ?int $handled_by = null,
        ?string $handled_by_role = null,
        ?string $created_at = null,
        ?string $handled_at = null
    ) {
        $this->id = $id;
        $this->sender_id = $sender_id;
        $this->sender_role = $sender_role;
        $this->receiver_scope = $receiver_scope;
        $this->receiver_id = $receiver_id;
        $this->request_type = $request_type;
        $this->target_user_id = $target_user_id;
        $this->title = $title;
        $this->message = $message;
        $this->status = $status;
        $this->response_message = $response_message;
        $this->handled_by = $handled_by;
        $this->handled_by_role = $handled_by_role;
        $this->created_at = $created_at;
        $this->handled_at = $handled_at;
    }

    public static function fromArray(array $row): self
    {
        return new self(
            isset($row['id']) ? (int)$row['id'] : null,
            isset($row['sender_id']) ? (int)$row['sender_id'] : 0,
            (string)($row['sender_role'] ?? ''),
            (string)($row['receiver_scope'] ?? ''),
            isset($row['receiver_id']) && $row['receiver_id'] !== null ? (int)$row['receiver_id'] : null,
            (string)($row['request_type'] ?? 'other'),
            isset($row['target_user_id']) && $row['target_user_id'] !== null ? (int)$row['target_user_id'] : null,
            (string)($row['title'] ?? ''),
            (string)($row['message'] ?? ''),
            (string)($row['status'] ?? 'pending'),
            isset($row['response_message']) ? (string)$row['response_message'] : null,
            isset($row['handled_by']) && $row['handled_by'] !== null ? (int)$row['handled_by'] : null,
            isset($row['handled_by_role']) ? (string)$row['handled_by_role'] : null,
            isset($row['created_at']) ? (string)$row['created_at'] : null,
            isset($row['handled_at']) ? (string)$row['handled_at'] : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'sender_role' => $this->sender_role,
            'receiver_scope' => $this->receiver_scope,
            'receiver_id' => $this->receiver_id,
            'request_type' => $this->request_type,
            'target_user_id' => $this->target_user_id,
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->status,
            'response_message' => $this->response_message,
            'handled_by' => $this->handled_by,
            'handled_by_role' => $this->handled_by_role,
            'created_at' => $this->created_at,
            'handled_at' => $this->handled_at,
        ];
    }
}
