<?php

class Comment
{
    private ?string $id;
    private ?string $idPost;
    private ?string $idComment;
    private string $idUser;
    private string $text;
    private ?string $sticker;
    private ?string $image;
    private int $numberOfLike;
    private int $numberOfDislike;

    public function __construct(
        ?string $id = null,
        ?string $idPost = null,
        ?string $idComment = null,
        string $idUser = '',
        string $text = '',
        ?string $sticker = null,
        ?string $image = null,
        int $numberOfLike = 0,
        int $numberOfDislike = 0
    ) {
        $this->id = $id;
        $this->idPost = $idPost;
        $this->idComment = $idComment;
        $this->idUser = $idUser;
        $this->text = $text;
        $this->sticker = $sticker;
        $this->image = $image;
        $this->numberOfLike = $numberOfLike;
        $this->numberOfDislike = $numberOfDislike;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getIdPost(): ?string
    {
        return $this->idPost;
    }

    public function setIdPost(?string $idPost): void
    {
        $this->idPost = $idPost;
    }

    public function getIdComment(): ?string
    {
        return $this->idComment;
    }

    public function setIdComment(?string $idComment): void
    {
        $this->idComment = $idComment;
    }

    public function getIdUser(): string
    {
        return $this->idUser;
    }

    public function setIdUser(string $idUser): void
    {
        $this->idUser = $idUser;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function getSticker(): ?string
    {
        return $this->sticker;
    }

    public function setSticker(?string $sticker): void
    {
        $this->sticker = $sticker;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getNumberOfLike(): int
    {
        return $this->numberOfLike;
    }

    public function setNumberOfLike(int $numberOfLike): void
    {
        $this->numberOfLike = $numberOfLike;
    }

    public function getNumberOfDislike(): int
    {
        return $this->numberOfDislike;
    }

    public function setNumberOfDislike(int $numberOfDislike): void
    {
        $this->numberOfDislike = $numberOfDislike;
    }
}