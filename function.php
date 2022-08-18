public function getShortestPath(int $uid1, int $uid2, int $deep): array
    {
        $subQuery = \DB::table('facebook_friends', 'a')
            ->addSelect('a.user_id as User0');

        $notIn = [];
        $resultIn = [];

        for ($i = 0; $i <= $deep; $i++) {
            if ($i !== 0) {
                $notIn[] = sprintf('%s.user_id', $this->chars[$i - 1]);
                $resultIn[] = sprintf('result.User%s', $i);

                $subQuery
                    ->addSelect(sprintf('%s.friend_id as User%s', $this->chars[$i - 1], $i))
                    ->leftJoin(
                        sprintf('facebook_friends as %s', $this->chars[$i]),
                        function (JoinClause $join) use (&$i, &$notIn) {
                            // joining user with friend
                            $join->on(
                                sprintf('%s.user_id', $this->chars[$i]),
                                '=',
                                sprintf('%s.friend_id', $this->chars[$i - 1])
                            );
                            // exclude self join
                            $join->whereRaw(
                                sprintf('%s.friend_id not in(%s)', $this->chars[$i], implode(',', $notIn))
                            );
                        });

            }
        }

        $subQuery->where('a.user_id', $uid1);

        $query = \DB::query()
            ->from($subQuery, 'result')
            ->whereRaw(sprintf('%d in(%s)', $uid2, implode(',', $resultIn)));

        // Getting all paths.
        $result = $query->get();

        // Filtering the shortest one.
        return $result
            ->map(fn($record) => array_filter(array_values((array) $record)))
            ->sortBy(fn($record) => count($record))
            ->first();
    }
