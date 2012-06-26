<?php
/**
 * Project load strategy using raw git objects
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Git
 */
class GitPHP_ProjectLoad_Raw implements GitPHP_ProjectLoadStrategy_Interface
{
	/**
	 * Load a project's epoch
	 *
	 * @param GitPHP_Project $project project
	 * @return string epoch
	 */
	public function LoadEpoch($project)
	{
		if (!$project)
			return;

		$epoch = 0;
		foreach ($project->GetHeadList() as $headObj) {
			$commit = $headObj->GetCommit();
			if ($commit) {
				if ($commit->GetCommitterEpoch() > $epoch) {
					$epoch = $commit->GetCommitterEpoch();
				}
			}
		}
		if ($epoch > 0) {
			return $epoch;
		}
	}

	/**
	 * Load a project's head hash
	 *
	 * @param GitPHP_Project $project
	 * @return string head hash
	 */
	public function LoadHead($project)
	{
		if (!$project)
			return;

		$headPointer = trim(file_get_contents($project->GetPath() . '/HEAD'));
		if (preg_match('/^([0-9A-Fa-f]{40})$/', $headPointer, $regs)) {
			/* Detached HEAD */
			return $regs[1];
		} else if (preg_match('/^ref: (.+)$/', $headPointer, $regs)) {
			/* standard pointer to head */
			$head = substr($regs[1], strlen('refs/heads/'));

			if ($project->GetHeadList()->Exists($head))
				return $project->GetHeadList()->GetHead($head)->GetHash();
		}
	}

	/**
	 * Expand an abbreviated hash
	 *
	 * @param GitPHP_Project $project project
	 * @param string $abbrevHash abbreviated hash
	 * @return string full hash
	 */
	public function ExpandHash($project, $abbrevHash)
	{
		if (!$project)
			return $abbrevHash;

		if (!(preg_match('/[0-9A-Fa-f]{4,39}/', $abbrevHash))) {
			return $abbrevHash;
		}

		return $project->GetObjectLoader()->ExpandHash($abbrevHash);
	}

	/**
	 * Default raw abbreviation length
	 *
	 * @const
	 */
	const HashAbbreviateLength = 7;

	/**
	 * Abbreviate a hash
	 *
	 * @param GitPHP_Project $project project
	 * @param string $hash hash to abbreviate
	 * @return string abbreviated hash
	 */
	public function AbbreviateHash($project, $hash)
	{
		if (!$project)
			return $hash;

		if (!(preg_match('/[0-9A-Fa-f]{40}/', $hash))) {
			return $hash;
		}

		$abbrevLen = GitPHP_ProjectLoad_Raw::HashAbbreviateLength;

		if ($project->GetAbbreviateLength() > 0) {
			$abbrevLen = max(4, min($project->GetAbbreviateLength(), 40));
		}

		$prefix = substr($hash, 0, $abbrevLen);

		if (!$project->GetUniqueAbbreviation()) {
			return $prefix;
		}

		return $project->GetObjectLoader()->EnsureUniqueHash($hash, $prefix);
	}
}