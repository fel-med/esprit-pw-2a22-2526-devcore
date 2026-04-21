<?php

final class SocialElementType
{
    public const POST = 'post';
    public const COMMENT = 'comment';
}

class Comment
{
    private ?string $id = null;
    private string $idCommentedElement = '';
    private int $idUser = 0;
    private string $commentedItem = SocialElementType::POST;
    private string $text = '';
    private ?string $Sticker = null;
    private ?string $image = null;
    private int $numberOfLike = 0;
    private int $numberOfDislike = 0;

    public function __construct(
        ?string $id = null,
        ?string $idCommentedElement = '',
        int $idUser = 0,
        string $commentedItem = SocialElementType::POST,
        ?string $text = '',
        ?string $Sticker = null,
        ?string $image = null,
        int $numberOfLike = 0,
        int $numberOfDislike = 0
    ) {
        $this->id = $id;
        $this->idCommentedElement = (string)$idCommentedElement;
        $this->idUser = $idUser;
        $this->commentedItem = in_array(strtolower($commentedItem), [SocialElementType::POST, SocialElementType::COMMENT], true)
            ? strtolower($commentedItem)
            : SocialElementType::POST;
        $this->text = trim((string)$text);
        $this->Sticker = $Sticker;
        $this->image = $image;
        $this->numberOfLike = $numberOfLike;
        $this->numberOfDislike = $numberOfDislike;
    }

    public function getId(): ?string { return $this->id; }
    public function setId(?string $id): void { $this->id = $id; }

    public function getIdCommentedElement(): string { return $this->idCommentedElement; }
    public function setIdCommentedElement(?string $idCommentedElement): void { $this->idCommentedElement = trim((string)$idCommentedElement); }

    public function getIdUser(): int { return $this->idUser; }
    public function setIdUser(int $idUser): void { $this->idUser = $idUser; }

    public function getCommentedItem(): string { return $this->commentedItem; }
    public function setCommentedItem(string $commentedItem): void
    {
        $commentedItem = strtolower(trim($commentedItem));
        $this->commentedItem = in_array($commentedItem, [SocialElementType::POST, SocialElementType::COMMENT], true)
            ? $commentedItem
            : SocialElementType::POST;
    }

    public function getText(): string { return $this->text; }
    public function setText(?string $text): void { $this->text = trim((string)$text); }

    public function getSticker(): ?string { return $this->Sticker; }
    public function setSticker(?string $Sticker): void { $this->Sticker = $Sticker !== null && trim($Sticker) !== '' ? trim($Sticker) : null; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): void { $this->image = $image !== null && trim($image) !== '' ? trim($image) : null; }

    public function getNumberOfLike(): int { return $this->numberOfLike; }
    public function setNumberOfLike(int $numberOfLike): void { $this->numberOfLike = $numberOfLike; }

    public function getNumberOfDislike(): int { return $this->numberOfDislike; }
    public function setNumberOfDislike(int $numberOfDislike): void { $this->numberOfDislike = $numberOfDislike; }
}
