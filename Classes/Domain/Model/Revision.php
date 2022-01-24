<?php
declare(strict_types=1);

namespace CodeQ\Revisions\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Now;

/**
 * @Flow\Entity
 */
class Revision
{

    /**
     * @var \DateTime
     */
    protected $creationDateTime;

    /**
     * @var string
     */
    protected $creator;

    /**
     * @var string
     * @ORM\Column(type="blob")
     */
    protected $content;

    /**
     * @var string
     */
    protected $nodeIdentifier;

    public function __construct(string $nodeIdentifier, string $creator, string $content)
    {
        $this->creationDateTime = new Now();
        $this->creator = $creator;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->content = bzcompress($content, 9);
    }

    public function getCreationDateTime(): \DateTime
    {
        return $this->creationDateTime;
    }

    public function getNodeIdentifier(): string
    {
        return $this->nodeIdentifier;
    }

    public function getCreator(): string
    {
        return $this->creator;
    }

    public function getContent(): string
    {
        return bzdecompress($this->content);
    }

}
